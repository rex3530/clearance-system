<?php include '../includes/auth_admin.php'; ?>
<?php include '../includes/header.php'; ?>
<?php require __DIR__ . '/../includes/db.php'; ?>
<?php
$rid = (int)($_GET['id'] ?? 0);
$request = null; $officeRows = [];
if ($rid > 0) {
  // Load request + student
  $stmt = $conn->prepare('SELECT cr.id, cr.student_id, cr.comments, cr.overall_status, cr.created_at, cr.updated_at, s.name AS student_name, s.student_id AS student_code, s.department FROM clearance_requests cr JOIN students s ON s.id = cr.student_id WHERE cr.id = ?');
  $stmt->bind_param('i', $rid);
  $stmt->execute();
  $res = $stmt->get_result();
  $request = $res->fetch_assoc();
  if ($request) {
    $q = $conn->prepare('SELECT o.name AS office_name, oc.status, oc.comment, oc.updated_at FROM office_clearance oc JOIN offices o ON o.id = oc.office_id WHERE oc.request_id = ? ORDER BY o.name');
    $q->bind_param('i', $rid);
    $q->execute();
    $rs = $q->get_result();
    while ($row = $rs->fetch_assoc()) { $officeRows[] = $row; }
  }
}
function badge_class_a($s){ if($s==='approved')return 'badge-approved'; if($s==='rejected')return 'badge-rejected'; return 'badge-pending'; }
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Request Details <?php echo $rid?('#'.(int)$rid):''; ?></h5>
    <a href="admin/admin_dashboard.php" class="btn btn-outline-hu btn-sm">Back</a>
  </div>

  <?php if (!$rid || !$request): ?>
    <div class="alert alert-warning">Request not found.</div>
  <?php else: ?>
    <div class="row g-3">
      <div class="col-md-4">
        <div class="card">
          <div class="card-body">
            <div class="fw-semibold text-muted">Student</div>
            <div><?php echo htmlspecialchars($request['student_name']); ?> (<?php echo htmlspecialchars($request['student_code']); ?>)</div>
            <div class="small text-muted mt-1">Dept: <?php echo htmlspecialchars($request['department']); ?></div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card">
          <div class="card-body">
            <div class="fw-semibold text-muted">Overall Status</div>
            <?php $cls = badge_class_a($request['overall_status']); ?>
            <div><span class="badge <?php echo $cls; ?> rounded-pill px-3 py-2"><?php echo ucfirst($request['overall_status']); ?></span></div>
            <div class="small text-muted mt-1">Updated: <?php echo htmlspecialchars($request['updated_at']); ?></div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card">
          <div class="card-body">
            <div class="fw-semibold text-muted">Student Comment</div>
            <div class="small"><?php echo nl2br(htmlspecialchars($request['comments'] ?? '')); ?></div>
            <div class="small text-muted mt-2">Created: <?php echo htmlspecialchars($request['created_at']); ?></div>
          </div>
        </div>
      </div>
    </div>

    <div class="card mt-4">
      <div class="card-body">
        <h6 class="text-muted mb-2">Per-office Status</h6>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr>
                <th>#</th>
                <th>Office</th>
                <th>Status</th>
                <th>Comment</th>
                <th>Updated</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($officeRows)): ?>
                <tr><td colspan="5" class="text-center text-muted-600">No offices seeded for this request.</td></tr>
              <?php else: foreach ($officeRows as $i=>$r): $cls=badge_class_a($r['status']); ?>
                <tr>
                  <td><?php echo $i+1; ?></td>
                  <td><?php echo htmlspecialchars(ucfirst($r['office_name'])); ?></td>
                  <td><span class="badge <?php echo $cls; ?>"><?php echo ucfirst($r['status']); ?></span></td>
                  <td><?php echo nl2br(htmlspecialchars($r['comment'] ?? '')); ?></td>
                  <td><?php echo htmlspecialchars($r['updated_at']); ?></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>
