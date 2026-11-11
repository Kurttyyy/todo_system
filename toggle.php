<?php
require 'database.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}
verify_csrf();
$id = (int)($_POST['id'] ?? 0);
if ($id < 1) {
    http_response_code(400);
    exit('Invalid task ID.');
}
$userId = (int)$_SESSION['user_id'];
$rows = sb('GET', 'tasks?select=status&limit=1&task_id=eq.' . $id . '&user_id=eq.' . $userId);
if ($rows) {
    $new = $rows[0]['status'] === 'Pending' ? 'Completed' : 'Pending';
    sb('PATCH', 'tasks?task_id=eq.' . $id . '&user_id=eq.' . $userId, ['status' => $new]);
}
header('Location: dashboard.php?msg=' . urlencode('Task status updated.'));
exit;
