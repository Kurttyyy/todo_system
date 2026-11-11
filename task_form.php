<?php /* Shared form for add.php and edit.php. Expects $errors and $data. */ ?>
<?php if ($errors): ?>
  <div class="alert alert-error"><ul>
    <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
  </ul></div>
<?php endif; ?>
<form method="post">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int)($id ?? 0) ?>">
  <div class="field">
    <label for="task_title">Task Title *</label>
    <input class="form-control" type="text" id="task_title" name="task_title" maxlength="150" value="<?= e($data['title']) ?>" required>
  </div>
  <div class="field">
    <label for="description">Description</label>
    <textarea class="form-control" id="description" name="description" rows="3"><?= e($data['desc']) ?></textarea>
  </div>
  <div class="row">
    <div class="field">
      <label for="due_date">Due Date *</label>
      <input class="form-control" type="date" id="due_date" name="due_date" value="<?= e($data['due']) ?>" required>
    </div>
    <div class="field">
      <label for="status">Status</label>
      <select class="form-select" id="status" name="status">
        <option <?= $data['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
        <option <?= $data['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
      </select>
    </div>
  </div>
  <button class="btn btn-primary">Save Task</button>
  <a href="dashboard.php" class="btn">Cancel</a>
</form>
