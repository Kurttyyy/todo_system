<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($page_title ?? 'To-Do List') ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<link rel="stylesheet" href="css/style.css?v=professional-1">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous" defer></script>
<script src="js/script.js" defer></script>
</head>
<body>
<div class="navbar">
  <div class="container">
    <a class="brand" href="dashboard.php"><span class="logo">✓</span> To-Do List</a>
    <div class="user">
      <div class="user-chip">
        <div class="avatar"><?= e(strtoupper(substr($_SESSION['username'], 0, 1))) ?></div>
        <span><?= e($_SESSION['username']) ?></span>
      </div>
      <a href="logout.php" class="btn btn-sm btn-light">Logout</a>
    </div>
  </div>
</div>
