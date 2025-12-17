<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../includes/auth_office.php';
require __DIR__ . '/../includes/db.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
  $requestId = (int)($_POST['request_id'] ?? 0);
  $status = $_POST['status'] ?? '';
  $comment = trim($_POST['comment'] ?? '');
  $officeId = (int)($_SESSION['user']['office_id'] ?? 0);

  if ($requestId > 0 && $officeId > 0 && in_array($status, ['approved','rejected','pending'], true)) {
    // Update office_clearance for this office
    $stmt = $conn->prepare('UPDATE office_clearance SET status = ?, comment = ?, updated_at = NOW() WHERE request_id = ? AND office_id = ?');
    $stmt->bind_param('ssii', $status, $comment, $requestId, $officeId);
    $stmt->execute();

    // Recompute overall status
    $all = $conn->prepare('SELECT status FROM office_clearance WHERE request_id = ?');
    $all->bind_param('i', $requestId);
    $all->execute();
    $res = $all->get_result();
    $hasRejected = false; $allApproved = true;
    while ($row = $res->fetch_assoc()) {
      $s = $row['status'];
      if ($s === 'rejected') { $hasRejected = true; }
      if ($s !== 'approved') { $allApproved = false; }
    }
    $overall = 'pending';
    if ($hasRejected) $overall = 'rejected';
    elseif ($allApproved) $overall = 'approved';

    $up = $conn->prepare('UPDATE clearance_requests SET overall_status = ?, updated_at = NOW() WHERE id = ?');
    $up->bind_param('si', $overall, $requestId);
    $up->execute();
  }

  header('Location: office_dashboard.php?updated=1');
  exit;
}

header('Location: office_dashboard.php');
exit;
?>
