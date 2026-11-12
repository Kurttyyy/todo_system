<?php
require 'database.php';
require_login();
$page_title = 'Dashboard | To-Do List';

// Summary counts
$userId = (int)$_SESSION['user_id'];
$all = sb('GET', 'tasks?select=status,due_date&user_id=eq.' . $userId);
$today = date('Y-m-d');
$counts = ['total' => count($all), 'pending' => 0, 'completed' => 0, 'overdue' => 0];
foreach ($all as $r) {
    if ($r['status'] === 'Completed') {
        $counts['completed']++;
    } else {
        $counts['pending']++;
        if ($r['due_date'] < $today) $counts['overdue']++;
    }
}
$percent = $counts['total'] ? (int)round($counts['completed'] / $counts['total'] * 100) : 0;

// Search + status filter
$q = trim($_GET['q'] ?? '');
$filter = $_GET['status'] ?? '';
$path = 'tasks?select=*&user_id=eq.' . $userId . '&order=status.desc,due_date.asc';
if ($q !== '') {
    $term = rawurlencode(str_replace([',', '(', ')', '*', '"'], ' ', $q));
    $path .= '&or=' . rawurlencode('(') . 'task_title.ilike.*' . $term . '*,description.ilike.*' . $term . '*' . rawurlencode(')');
}
if (in_array($filter, ['Pending', 'Completed'], true)) {
    $path .= '&status=eq.' . $filter;
}
$tasks = sb('GET', $path);

include 'header.php';
?>
<div class="container">

  <div class="page-head">
    <div>
      <span class="eyebrow">YOUR WORKSPACE</span>
      <h1>Dashboard</h1>
      <p><time id="today-date"><?= e(date('l, F j, Y')) ?></time> <span aria-hidden="true">&middot;</span> Keep your day organized and on track.</p>
    </div>
    <a href="add.php" class="btn btn-primary add-task-btn"><span aria-hidden="true">+</span> Add Task</a>
  </div>

  <?php if (!empty($_GET['msg'])): ?>
    <div class="alert alert-success"><?= e($_GET['msg']) ?></div>
  <?php endif; ?>

  <section class="stats" aria-label="Task summary">
    <div class="card stat"><div class="stat-icon i-all" aria-hidden="true">▤</div><div><strong><?= (int)$counts['total'] ?></strong><span>Total tasks</span></div></div>
    <div class="card stat"><div class="stat-icon i-warn" aria-hidden="true">◷</div><div><strong><?= (int)$counts['pending'] ?></strong><span>Pending</span></div></div>
    <div class="card stat"><div class="stat-icon i-ok" aria-hidden="true">✓</div><div><strong><?= (int)$counts['completed'] ?></strong><span>Completed</span></div></div>
    <div class="card stat"><div class="stat-icon i-bad" aria-hidden="true">!</div><div><strong><?= (int)$counts['overdue'] ?></strong><span>Overdue</span></div></div>
  </section>

  <section class="card progress-card" aria-label="Overall task progress">
    <div class="progress-top">
      <div><strong>Overall progress</strong><span class="progress-caption">Tasks completed</span></div>
      <span class="progress-percent"><?= $percent ?>%</span>
    </div>
    <div class="bar" role="progressbar" aria-valuenow="<?= $percent ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Tasks completed">
      <div style="width: <?= $percent ?>%"></div>
    </div>
  </section>

  <section class="card task-panel">
    <div class="task-panel-head">
      <div>
        <h2>Your tasks</h2>
        <p>Review and manage your task list.</p>
      </div>
      <span class="task-count"><?= count($tasks) ?> <?= count($tasks) === 1 ? 'task' : 'tasks' ?></span>
    </div>

    <div class="toolbar">
      <form method="get" role="search">
        <label class="sr-only" for="task-search">Search tasks</label>
        <input class="form-control" type="search" id="task-search" name="q" value="<?= e($q) ?>" placeholder="Search tasks...">
        <label class="sr-only" for="task-status">Filter by status</label>
        <select class="form-select" id="task-status" name="status">
          <option value="">All status</option>
          <option <?= $filter === 'Pending' ? 'selected' : '' ?>>Pending</option>
          <option <?= $filter === 'Completed' ? 'selected' : '' ?>>Completed</option>
        </select>
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if ($q !== '' || $filter !== ''): ?><a href="dashboard.php" class="btn">Reset</a><?php endif; ?>
      </form>
    </div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>#</th><th>Task</th><th>Description</th><th>Due Date</th><th>Status</th><th class="right">Actions</th></tr>
        </thead>
        <tbody>
        <?php if (!$tasks): ?>
          <tr><td colspan="6" class="empty">
            <span class="empty-icon" aria-hidden="true">✓</span>
            <strong>No tasks found</strong>
            <span>Try changing your search or add a new task.</span>
          </td></tr>
        <?php endif; ?>
        <?php foreach ($tasks as $t):
            $done = $t['status'] === 'Completed';
            $overdue = !$done && $t['due_date'] < $today; ?>
          <tr>
            <td data-label="#" class="muted task-id"><?= (int)$t['task_id'] ?></td>
            <td data-label="Task" class="<?= $done ? 'done' : 'bold' ?> task-title"><?= e($t['task_title']) ?></td>
            <td data-label="Description" class="muted small task-description"><?= e($t['description']) ?></td>
            <td data-label="Due date" class="<?= $overdue ? 'due-bad' : '' ?>">
              <?= e(date('M d, Y', strtotime($t['due_date']))) ?>
              <?php if ($overdue): ?><span class="badge badge-bad">Overdue</span><?php endif; ?>
            </td>
            <td data-label="Status"><span class="badge <?= $done ? 'badge-ok' : 'badge-warn' ?>"><?= e($t['status']) ?></span></td>
            <td data-label="Actions" class="actions">
              <div class="action-buttons">
                <form action="toggle.php" method="post">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$t['task_id'] ?>">
                  <button class="btn btn-sm <?= $done ? '' : 'btn-success' ?>"><?= $done ? 'Mark Pending' : 'Mark Done' ?></button>
                </form>
                <a href="edit.php?id=<?= (int)$t['task_id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                <form action="delete.php" method="post" data-confirm="Delete this task? This cannot be undone.">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$t['task_id'] ?>">
                  <button class="btn btn-sm btn-danger">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>
</body>
</html>
