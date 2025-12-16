<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user'])) {
  header('Location: ../login.php');
  exit;
}
if (($_SESSION['user']['role'] ?? '') !== 'office') {
  header('Location: ../login.php');
  exit;
}
?>
