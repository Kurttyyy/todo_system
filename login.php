<?php
require 'database.php';
$mode = ($_GET['mode'] ?? '') === 'register' ? 'register' : 'login';
$errors = [];
$username = '';
$appUrl = 'https://todo-system-xmck.onrender.com';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $mode = ($_POST['mode'] ?? '') === 'register' ? 'register' : 'login';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($mode === 'register') {
        $confirmPassword = $_POST['confirm_password'] ?? '';
        if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $username)) {
            $errors[] = 'Username must be 3-30 characters and contain only letters, numbers, and underscores.';
        }
        if (strlen($password) < 8 || strlen($password) > 72) {
            $errors[] = 'Password must be between 8 and 72 characters.';
        }
        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }

        if (!$errors) {
            try {
                sb('POST', 'users', [[
                    'username' => $username,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                ]]);
                header('Location: login.php?registered=1');
                exit;
            } catch (RuntimeException $error) {
                if ((int)$error->getCode() === 409) {
                    $errors[] = 'That username is already taken.';
                } else {
                    error_log('Account registration failed: ' . $error->getMessage());
                    $errors[] = 'We could not create your account right now. Please try again later.';
                }
            }
        }
    } elseif ($username === '' || $password === '') {
        $errors[] = 'Please enter your username and password.';
    } else {
        try {
            $rows = sb('GET', 'users?select=*&limit=1&username=eq.' . rawurlencode($username));
            $u = $rows[0] ?? null;
            if ($u && password_verify($password, $u['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $u['user_id'];
                $_SESSION['username'] = $u['username'];
                header('Location: dashboard.php');
                exit;
            }
            $errors[] = 'Invalid username or password.';
        } catch (RuntimeException $error) {
            error_log('Login database check failed: ' . $error->getMessage());
            $errors[] = (int) $error->getCode() === 404
                ? 'Database tables are not set up yet. Run supabase.sql in the Supabase SQL Editor.'
                : 'We could not connect to the database right now. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $mode === 'register' ? 'Create account' : 'Login' ?> | To-Do List</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<link rel="stylesheet" href="css/style.css?v=003a6a4">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous" defer></script>
<script src="js/script.js" defer></script>
</head>
<body>
<div class="login-page">
  <div class="card login-box">
    <div class="login-logo">✓</div>
    <h2 class="center"><?= $mode === 'register' ? 'Create an account' : 'Welcome back' ?></h2>
    <p class="center muted"><?= $mode === 'register' ? 'Sign up to start managing your own tasks' : 'Sign in to manage your tasks' ?></p>
    <?php if ($mode === 'login' && ($_GET['registered'] ?? '') === '1'): ?><div class="alert alert-success">Account created. You can now sign in.</div><?php endif; ?>
    <?php if ($errors): ?><div class="alert alert-error"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="mode" value="<?= e($mode) ?>">
      <div class="field">
        <label for="username">Username</label>
        <input class="form-control" type="text" id="username" name="username" value="<?= e($username) ?>" placeholder="Enter your username" <?= $mode === 'register' ? 'minlength="3" maxlength="30" pattern="[A-Za-z0-9_]{3,30}" autocomplete="username"' : '' ?> required autofocus>
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input class="form-control" type="password" id="password" name="password" placeholder="Enter your password" <?= $mode === 'register' ? 'minlength="8" maxlength="72" autocomplete="new-password"' : '' ?> required>
      </div>
      <?php if ($mode === 'register'): ?>
      <div class="field">
        <label for="confirm_password">Confirm password</label>
        <input class="form-control" type="password" id="confirm_password" name="confirm_password" minlength="8" maxlength="72" autocomplete="new-password" required>
      </div>
      <?php endif; ?>
      <button class="btn btn-primary btn-block w-100"><?= $mode === 'register' ? 'Create account' : 'Sign in' ?></button>
    </form>
    <?php if ($mode === 'login'): ?>
    <button type="button" class="btn btn-outline qr-trigger" id="show-qr" aria-haspopup="dialog" aria-controls="qr-dialog">▣ Show QR for easy access</button>
    <?php endif; ?>
    <p class="center muted small" style="margin:18px 0 0">
      <?= $mode === 'register' ? 'Already have an account? <a href="login.php">Sign in</a>' : 'New here? <a href="login.php?mode=register">Create an account</a>' ?>
    </p>
  </div>
</div>
<?php if ($mode === 'login'): ?>
<div class="qr-dialog" id="qr-dialog" role="dialog" aria-modal="true" aria-labelledby="qr-title" hidden>
  <div class="qr-dialog-card">
    <button type="button" class="qr-close" id="close-qr" aria-label="Close QR code">&times;</button>
    <h2 id="qr-title">Open To-Do List</h2>
    <p class="muted small">Scan this QR code using your phone camera.</p>
    <img class="qr-image" src="https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=<?= rawurlencode($appUrl) ?>" alt="QR code for To-Do List">
    <p class="qr-url"><?= e($appUrl) ?></p>
  </div>
</div>
<?php endif; ?>
</body>
</html>
