<?php
// Supabase connection + shared helpers
date_default_timezone_set('Asia/Manila');
session_start();

// Set these in the PHP hosting environment; never put the secret key in browser code.
$SUPABASE_URL = getenv('SUPABASE_URL');
$SUPABASE_KEY = getenv('SUPABASE_SECRET_KEY');
// Set to false ONLY if you get an "SSL certificate problem" error on XAMPP
$SSL_VERIFY = true;

// Sends a request to Supabase's REST API. Returns the decoded JSON rows.
function sb($method, $path, $body = null) {
    global $SUPABASE_URL, $SUPABASE_KEY, $SSL_VERIFY;
    if (!$SUPABASE_URL || !$SUPABASE_KEY) {
        die('Set the SUPABASE_URL and SUPABASE_SECRET_KEY environment variables before using the app.');
    }
    $ch = curl_init(rtrim($SUPABASE_URL, '/') . '/rest/v1/' . $path);
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
            'Prefer: return=minimal',
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