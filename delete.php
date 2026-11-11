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
sb('DELETE', 'tasks?task_id=eq.' . $id . '&user_id=eq.' . (int)$_SESSION['user_id']);
header('Location: dashboard.php?msg=' . urlencode('Task deleted.'));
exit;
