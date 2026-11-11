<?php
require 'database.php';
header('Location: ' . (empty($_SESSION['user_id']) ? 'login.php' : 'dashboard.php'));
exit;
