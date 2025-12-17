<?php include '../includes/auth_office.php'; ?>
<?php include '../includes/header.php'; ?>
<?php require __DIR__ . '/../includes/db.php'; ?>
<?php
$officeId = (int)($_SESSION['user']['office_id'] ?? 0);
$rows = [];
if ($officeId > 0) {
  $stmt = $conn->prepare(
    'SELECT cr.id AS request_id, cr.created_at, cr.overall_status, cr.comments,
            s.name AS student_name, s.student_id AS student_code, s.department,
            oc.status AS office_status
     FROM office_clearance oc
     JOIN clearance_requests cr ON cr.id = oc.request_id
     JOIN students s ON s.id = cr.student_id
     WHERE oc.office_id = ?
     ORDER BY cr.created_at DESC'
  );
  $stmt->bind_param('i', $officeId);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($r = $res->fetch_assoc()) { $rows[] = $r; }
}
?>
<div class="container py-4">
  <div class="card mb-3">
    <div class="card-body d-flex justify-content-between align-items-center">
      <div>
        <h5 class="mb-1">Office Dashboard</h5>
        <div class="text-muted-600">Logged in as: <strong><?php echo htmlspecialchars($_SESSION['user']['office_name'] ?? ''); ?></strong></div>
      </div>
    </div>
  </div>
  <div class="card">
    <div class="card-body">
      <?php if (!empty($_GET['updated'])): ?>
        <div class="alert alert-success" role="alert">Status updated.</div>
      <?php endif; ?>
      <h5 class="mb-3">Incoming Clearance Requests</h5>
      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>#</th>
              <th>Student</th>
              <th>Department</th>
              <th>Comment</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($rows)): ?>
              <tr>
                <td colspan="6" class="text-center text-muted-600">No requests for this office yet.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($rows as $i => $row): ?>
                <tr>
                  <td><?php echo $i+1; ?></td>
                  <td><?php echo htmlspecialchars($row['student_name'] . ' (' . $row['student_code'] . ')'); ?></td>
                  <td><?php echo htmlspecialchars($row['department']); ?></td>
                  <td><?php echo nl2br(htmlspecialchars($row['comments'] ?? '')); ?></td>
                  <td>
                    <?php
                      $s = $row['office_status'];
                      $cls = $s==='approved'?'badge-approved':($s==='rejected'?'badge-rejected':'badge-pending');
                    ?>
                    <span class="badge <?php echo $cls; ?>"><?php echo ucfirst($s); ?></span>
                  </td>
                  <td class="d-flex gap-2">
                    <form action="office/update_status.php" method="post" class="d-inline">
                      <input type="hidden" name="request_id" value="<?php echo (int)$row['request_id']; ?>">
                      <input type="hidden" name="status" value="approved">
                      <button class="btn btn-sm btn-success" data-confirm="Approve this request?">Approve</button>
                    </form>
                    <form action="office/update_status.php" method="post" class="d-inline">
                      <input type="hidden" name="request_id" value="<?php echo (int)$row['request_id']; ?>">
                      <input type="hidden" name="status" value="rejected">
                      <input type="hidden" name="comment" value="">
                      <button class="btn btn-sm btn-danger" data-confirm="Reject this request? You can add comments later.">Reject</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
