<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../includes/auth_admin.php';
require __DIR__ . '/../includes/db.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
  $rid = (int)($_POST['request_id'] ?? 0);
  if ($rid > 0) {
    // Delete the clearance request; office_clearance rows will cascade-delete
    $stmt = $conn->prepare('DELETE FROM clearance_requests WHERE id = ?');
    $stmt->bind_param('i', $rid);
    $stmt->execute();
  }
  header('Location: admin_dashboard.php?deleted=1');
  exit;
}

header('Location: admin_dashboard.php');
exit;
