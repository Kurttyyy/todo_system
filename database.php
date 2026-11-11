
<?php
// Supabase connection + shared helpers
date_default_timezone_set('Asia/Manila');

// Get credentials from hosting environment variables.
$SUPABASE_URL = trim((string) getenv('SUPABASE_URL'));
$SUPABASE_KEY = trim((string) getenv('SUPABASE_SECRET_KEY'));

// Keep SSL verification enabled.
$SSL_VERIFY = true;

// Sends a request to Supabase REST API.
function sb($method, $path, $body = null, $prefer = 'return=minimal') {
    global $SUPABASE_URL, $SUPABASE_KEY, $SSL_VERIFY;

    if (!$SUPABASE_URL || !$SUPABASE_KEY) {
        error_log('SUPABASE CONFIG ERROR: Missing environment variables.');

        throw new RuntimeException(
            'Supabase configuration is missing.'
        );
    }

    $urlParts = parse_url($SUPABASE_URL);

    if (
        $urlParts === false ||
        !in_array($urlParts['scheme'] ?? '', ['https', 'http'], true) ||
        empty($urlParts['host'])
    ) {
        throw new RuntimeException(
            'Invalid SUPABASE_URL configuration.'
        );
    }

    if (preg_match('/[\r\n]/', $SUPABASE_KEY)) {
        throw new RuntimeException(
            'SUPABASE_SECRET_KEY contains an invalid line break.'
        );
    }

    $url = rtrim($SUPABASE_URL, '/') . '/rest/v1/' . $path;

    $ch = curl_init($url);

    if ($ch === false) {
        throw new RuntimeException(
            'Could not initialize Supabase connection.'
        );
    }

    $headers = [
        'apikey: ' . $SUPABASE_KEY,
        'Content-Type: application/json',
        'Prefer: ' . $prefer,
    ];

    // Legacy JWT keys require Authorization header.
    // New sb_secret_ keys use the apikey header.
    if (strpos($SUPABASE_KEY, 'sb_') !== 0) {
        $headers[] = 'Authorization: Bearer ' . $SUPABASE_KEY;
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => $SSL_VERIFY,
        CURLOPT_HTTPHEADER => $headers,
    ]);

    if ($body !== null) {
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            json_encode($body, JSON_THROW_ON_ERROR)
        );
    }

    $res = curl_exec($ch);

    if ($res === false) {
        $error = curl_error($ch);
        $errorCode = curl_errno($ch);

        error_log(
            'SUPABASE CURL ERROR [' . $errorCode . ']: ' . $error
        );

        curl_close($ch);

        throw new RuntimeException(
            'Could not reach Supabase. Check connection and SSL settings.'
        );
    }

    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    // Log Supabase API errors for Vercel Runtime Logs.
    if ($code >= 400) {
        error_log('SUPABASE HTTP STATUS: ' . $code);
        error_log('SUPABASE RESPONSE: ' . substr($res, 0, 2000));

        throw new RuntimeException(
            'Supabase request failed with HTTP ' . $code,
            $code
        );
    }

    if ($res === '' || $res === null) {
        return [];
    }

    $decoded = json_decode($res, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log(
            'SUPABASE JSON ERROR: ' . json_last_error_msg()
        );

        throw new RuntimeException(
            'Invalid JSON response from Supabase.'
        );
    }

    return is_array($decoded) ? $decoded : [];
}


// ========================================
// SUPABASE SESSION HANDLER
// ========================================

class SupabaseSessionHandler implements SessionHandlerInterface {

    public function open(string $path, string $name): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read(string $id): string|false {

        $path = 'app_sessions?select=session_data&session_id=eq.'
            . rawurlencode($id)
            . '&expires_at=gt.'
            . rawurlencode(gmdate('Y-m-d\TH:i:s\Z'));

        $rows = sb('GET', $path);

        if (!$rows) {
            return '';
        }

        $data = base64_decode(
            $rows[0]['session_data'],
            true
        );

        if ($data === false) {
            throw new UnexpectedValueException(
                'Stored session data is invalid.'
            );
        }

        return $data;
    }

    public function write(string $id, string $data): bool {

        $lifetime = max(
            1,
            (int) ini_get('session.gc_maxlifetime')
        );

        sb(
            'POST',
            'app_sessions?on_conflict=session_id',
            [[
                'session_id' => $id,
                'session_data' => base64_encode($data),
                'expires_at' => gmdate(
                    'Y-m-d\TH:i:s\Z',
                    time() + $lifetime
                ),
            ]],
            'resolution=merge-duplicates,return=minimal'
        );

        return true;
    }

    public function destroy(string $id): bool {

        sb(
            'DELETE',
            'app_sessions?session_id=eq.' . rawurlencode($id)
        );

        return true;
    }

    public function gc(int $max_lifetime): int|false {

        $path = 'app_sessions?select=session_id&expires_at=lt.'
            . rawurlencode(gmdate('Y-m-d\TH:i:s\Z'));

        $rows = sb(
            'DELETE',
            $path,
            null,
            'return=representation'
        );

        return count($rows);
    }
}


// Register Supabase session handler.
session_set_save_handler(
    new SupabaseSessionHandler(),
    true
);

session_start();


// ========================================
// AUTHENTICATION HELPERS
// ========================================

function require_login() {

    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}


// ========================================
// GENERAL HELPERS
// ========================================

function e($v) {
    return htmlspecialchars(
        (string) $v,
        ENT_QUOTES,
        'UTF-8'
    );
}


function csrf_token() {

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(
            random_bytes(32)
        );
    }

    return $_SESSION['csrf_token'];
}


function csrf_field() {

    return '<input type="hidden" name="csrf_token" value="'
        . e(csrf_token())
        . '">';
}


function verify_csrf() {

    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        http_response_code(400);

        exit(
            'Invalid form submission. Refresh the page and try again.'
        );
    }
}


// ========================================
// TASK VALIDATION
// ========================================

function validate_task($input) {

    $errors = [];

    $title = trim($input['task_title'] ?? '');
    $desc = trim($input['description'] ?? '');
    $due = trim($input['due_date'] ?? '');
    $status = $input['status'] ?? 'Pending';

    if ($title === '') {
        $errors[] = 'Task title is required.';
    } elseif (mb_strlen($title) > 150) {
        $errors[] = 'Task title must be 150 characters or less.';
    }

    $d = DateTime::createFromFormat('Y-m-d', $due);

    if (!$d || $d->format('Y-m-d') !== $due) {
        $errors[] = 'A valid due date is required.';
    }

    if (!in_array($status, ['Pending', 'Completed'], true)) {
        $errors[] = 'Invalid status.';
    }

    return [
        $errors,
        [
            'title' => $title,
            'desc' => $desc,
            'due' => $due,
            'status' => $status,
        ]
    ];
}
