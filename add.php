<?php
require 'database.php';
require_login();
$page_title = 'Add Task | To-Do List';
$errors = [];
$data = ['title' => '', 'desc' => '', 'due' => date('Y-m-d'), 'status' => 'Pending'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    [$errors, $data] = validate_task($_POST);
    if (!$errors) {
        sb('POST', 'tasks', [[
            'task_title' => $data['title'], 'description' => $data['desc'],
            'due_date' => $data['due'], 'status' => $data['status'],
            'user_id' => (int)$_SESSION['user_id'],
        ]]);
        header('Location: dashboard.php?msg=' . urlencode('Task added successfully.'));
        exit;
    }
}
include 'header.php';
?>
<div class="container narrow">
  <div class="card">
    <h3>Add Task</h3>
    <?php include 'task_form.php'; ?>
  </div>
</div>
</body>
</html>
