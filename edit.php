<?php
require 'database.php';
require_login();
$page_title = 'Edit Task | To-Do List';
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

$userId = (int)$_SESSION['user_id'];
$rows = sb('GET', 'tasks?select=*&limit=1&task_id=eq.' . $id . '&user_id=eq.' . $userId);
$task = $rows[0] ?? null;
if (!$task) { header('Location: dashboard.php'); exit; }

$errors = [];
$data = ['title' => $task['task_title'], 'desc' => $task['description'], 'due' => $task['due_date'], 'status' => $task['status']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    [$errors, $data] = validate_task($_POST);
    if (!$errors) {
        sb('PATCH', 'tasks?task_id=eq.' . $id . '&user_id=eq.' . $userId, [
            'task_title' => $data['title'], 'description' => $data['desc'],
            'due_date' => $data['due'], 'status' => $data['status'],
        ]);
        header('Location: dashboard.php?msg=' . urlencode('Task updated successfully.'));
        exit;
    }
}
include 'header.php';
?>
<div class="container narrow">
  <div class="card">
    <h3>Edit Task #<?= $id ?></h3>
    <?php include 'task_form.php'; ?>
  </div>
</div>
</body>
</html>
