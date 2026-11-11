<?php
// Supabase connection + shared helpers
date_default_timezone_set('Asia/Manila');

// Set these in the PHP hosting environment; never put the secret key in browser code.
$SUPABASE_URL = trim((string) getenv('SUPABASE_URL'));
$SUPABASE_KEY = trim((string) getenv('SUPABASE_SECRET_KEY'));
// Set to false ONLY if you get an "SSL certificate problem" error on XAMPP
$SSL_VERIFY = true;

// Sends a request to Supabase's REST API. Returns the decoded JSON rows.
function sb($method, $path, $body = null, $prefer = 'return=minimal') {
    global $SUPABASE_URL, $SUPABASE_KEY, $SSL_VERIFY;
    if (!$SUPABASE_URL || !$SUPABASE_KEY) {
        die('Set the SUPABASE_URL and SUPABASE_SECRET_KEY environment variables before using the app.');
    }
    $urlParts = parse_url($SUPABASE_URL);
    if ($urlParts === false || !in_array($urlParts['scheme'] ?? '', ['https', 'http'], true) || empty($urlParts['host'])) {
        throw new RuntimeException('SUPABASE_URL must be the project URL, such as https://your-project.supabase.co.');
    }
    if (preg_match('/[\r\n]/', $SUPABASE_KEY)) {
        throw new RuntimeException('SUPABASE_SECRET_KEY contains an invalid line break. Re-enter the key without quotes or extra lines.');
    }

    $ch = curl_init(rtrim($SUPABASE_URL, '/') . '/rest/v1/' . $path);
    if ($ch === false) {
        throw new RuntimeException('Could not initialize the Supabase connection.');
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => $SSL_VERIFY,
        // New sb_secret_ keys go in the apikey header only; old JWT keys also need Authorization
        CURLOPT_HTTPHEADER     => array_filter([
            'apikey: ' . $SUPABASE_KEY,
            strpos($SUPABASE_KEY, 'sb_') === 0 ? null : 'Authorization: Bearer ' . $SUPABASE_KEY,
            'Content-Type: application/json',
            'Prefer: ' . $prefer,
        ]),
    ]);
    if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    $res = curl_exec($ch);
    if ($res === false) {
        $error = curl_error($ch);
        throw new RuntimeException('Could not reach Supabase: ' . $error);
    }
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($code >= 400) throw new RuntimeException('Supabase request failed.', $code);
    return json_decode($res, true) ?: [];
}

class SupabaseSessionHandler implements SessionHandlerInterface {
    public function open(string $path, string $name): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read(string $id): string|false {
        $path = 'app_sessions?select=session_data&session_id=eq.' . rawurlencode($id)
            . '&expires_at=gt.' . rawurlencode(gmdate('Y-m-d\TH:i:s\Z'));
        $rows = sb('GET', $path);
        if (!$rows) return '';

        $data = base64_decode($rows[0]['session_data'], true);
        if ($data === false) {
            throw new UnexpectedValueException('Stored session data is invalid.');
        }
        return $data;
    }

    public function write(string $id, string $data): bool {
        $lifetime = max(1, (int)ini_get('session.gc_maxlifetime'));
        sb('POST', 'app_sessions?on_conflict=session_id', [[
            'session_id' => $id,
            'session_data' => base64_encode($data),
            'expires_at' => gmdate('Y-m-d\TH:i:s\Z', time() + $lifetime),
        ]], 'resolution=merge-duplicates,return=minimal');
        return true;
    }

    public function destroy(string $id): bool {
        sb('DELETE', 'app_sessions?session_id=eq.' . rawurlencode($id));
        return true;
    }

    public function gc(int $max_lifetime): int|false {
        $path = 'app_sessions?select=session_id&expires_at=lt.'
            . rawurlencode(gmdate('Y-m-d\TH:i:s\Z'));
        $rows = sb('DELETE', $path, null, 'return=representation');
        return count($rows);
    }
}

session_set_save_handler(new SupabaseSessionHandler(), true);
session_start();

function require_login() {
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf() {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(400);
        exit('Invalid form submission. Refresh the page and try again.');
    }
}

// Shared validation for add/edit. Returns [errors, cleaned data]
function validate_task($input) {
    $errors = [];
    $title = trim($input['task_title'] ?? '');
    $desc  = trim($input['description'] ?? '');
    $due   = trim($input['due_date'] ?? '');
    $status = $input['status'] ?? 'Pending';

    if ($title === '') $errors[] = 'Task title is required.';
    elseif (mb_strlen($title) > 150) $errors[] = 'Task title must be 150 characters or less.';

    $d = DateTime::createFromFormat('Y-m-d', $due);
    if (!$d || $d->format('Y-m-d') !== $due) $errors[] = 'A valid due date is required.';

    if (!in_array($status, ['Pending', 'Completed'], true)) $errors[] = 'Invalid status.';

    return [$errors, ['title' => $title, 'desc' => $desc, 'due' => $due, 'status' => $status]];
}
