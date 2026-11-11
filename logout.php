<?php
require 'database.php';
session_destroy();
header('Location: login.php');
exit;
