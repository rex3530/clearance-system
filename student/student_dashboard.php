<?php include '../includes/auth_student.php'; ?>
<?php include '../includes/header.php'; ?>
<?php require __DIR__ . '/../includes/db.php'; ?>
<?php
$studentId = (int)($_SESSION['user']['id'] ?? 0);
$latest = null;
$offices = [];
// Quick stats
$countTotal = $countApproved = $countPending = $countRejected = 0;
if ($studentId > 0) {
  $stats = $conn->query("SELECT overall_status, COUNT(*) c FROM clearance_requests WHERE student_id = {$studentId} GROUP BY overall_status");
  if ($stats) {
    while ($row = $stats->fetch_assoc()) {
      if ($row['overall_status'] === 'approved') $countApproved = (int)$row['c'];
      elseif ($row['overall_status'] === 'rejected') $countRejected = (int)$row['c'];
      elseif ($row['overall_status'] === 'pending') $countPending = (int)$row['c'];
    }
  }
  $countTotal = $countApproved + $countRejected + $countPending;
}
if ($studentId > 0) {
  // Latest request
  $stmt = $conn->prepare('SELECT id, overall_status, created_at, updated_at FROM clearance_requests WHERE student_id = ? ORDER BY created_at DESC LIMIT 1');
  $stmt->bind_param('i', $studentId);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($latest = $res->fetch_assoc()) {
    $rid = (int)$latest['id'];
    $q = $conn->prepare('SELECT oc.status, oc.updated_at, o.name FROM office_clearance oc JOIN offices o ON o.id = oc.office_id WHERE oc.request_id = ? ORDER BY o.name');
    $q->bind_param('i', $rid);
    $q->execute();
    $rs = $q->get_result();
    while ($row = $rs->fetch_assoc()) { $offices[] = $row; }
  }
}
function badge_class($s){
  if ($s==='approved') return 'badge-approved';
  if ($s==='rejected') return 'badge-rejected';
  return 'badge-pending';
}

// Past requests filter & pagination
$statusFilter = isset($_GET['status']) ? strtolower(trim($_GET['status'])) : '';
if (!in_array($statusFilter, ['approved','pending','rejected',''], true)) { $statusFilter = ''; }
$page = max(1, (int)($_GET['page'] ?? 1));
$pageSize = 10;
$offset = ($page - 1) * $pageSize;

// Count
if ($studentId > 0) {
  if ($statusFilter === '') {
    $stmtC = $conn->prepare('SELECT COUNT(*) c FROM clearance_requests WHERE student_id = ?');
    $stmtC->bind_param('i', $studentId);
  } else {
    $stmtC = $conn->prepare('SELECT COUNT(*) c FROM clearance_requests WHERE student_id = ? AND overall_status = ?');
    $stmtC->bind_param('is', $studentId, $statusFilter);
  }
  $stmtC->execute();
  $totalRows = (int)$stmtC->get_result()->fetch_assoc()['c'];
  $totalPages = max(1, (int)ceil($totalRows / $pageSize));
  if ($page > $totalPages) { $page = $totalPages; $offset = ($page-1)*$pageSize; }

  if ($statusFilter === '') {
    $stmtL = $conn->prepare('SELECT id, overall_status, created_at, updated_at FROM clearance_requests WHERE student_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?');
    $stmtL->bind_param('iii', $studentId, $pageSize, $offset);
  } else {
    $stmtL = $conn->prepare('SELECT id, overall_status, created_at, updated_at FROM clearance_requests WHERE student_id = ? AND overall_status = ? ORDER BY created_at DESC LIMIT ? OFFSET ?');
    $stmtL->bind_param('isii', $studentId, $statusFilter, $pageSize, $offset);
  }
  $stmtL->execute();
  $past = $stmtL->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
  $totalRows = 0; $totalPages = 1; $past = [];
}
?>
<div class="container py-4">
  <div class="row g-3">
    <div class="col-md-4">
      <div class="card card-hover">
        <div class="card-body">
          <h6 class="text-muted">Overall Status</h6>
          <div class="mt-2">
            <?php if ($latest): ?>
              <?php $cls = badge_class($latest['overall_status']); ?>
              <span class="badge <?php echo $cls; ?> rounded-pill px-3 py-2"><?php echo ucfirst($latest['overall_status']); ?></span>
              <div class="small text-muted mt-2">Updated: <?php echo htmlspecialchars($latest['updated_at']); ?></div>
            <?php else: ?>
              <span class="badge badge-pending rounded-pill px-3 py-2">No Request</span>
            <?php endif; ?>
          </div>
          <p class="mt-2 text-muted-600">Approval required from Library, Bookstore, Sports.</p>
          <?php if (!$latest): ?>
            <a href="student/request_clearance.php" class="btn btn-hu btn-sm mt-2">Request Clearance</a>
          <?php endif; ?>
          <hr>
          <div class="d-grid gap-2">
            <div class="d-flex justify-content-between"><span class="text-muted">Total</span><strong><?php echo $countTotal; ?></strong></div>
            <div class="d-flex justify-content-between"><span class="text-muted">Approved</span><strong class="text-success"><?php echo $countApproved; ?></strong></div>
            <div class="d-flex justify-content-between"><span class="text-muted">Pending</span><strong class="text-warning"><?php echo $countPending; ?></strong></div>
            <div class="d-flex justify-content-between"><span class="text-muted">Rejected</span><strong class="text-danger"><?php echo $countRejected; ?></strong></div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-8">
      <div class="card card-hover h-100">
        <div class="card-body">
          <?php if (!empty($_GET['requested'])): ?>
            <div class="alert alert-success" role="alert">Clearance request submitted.</div>
          <?php endif; ?>
          <h6 class="text-muted mb-2">Per-office Status<?php echo $latest? ' (Latest Request #'.$latest['id'].')' : ''; ?></h6>
          <div class="table-responsive">
            <table class="table align-middle">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Office</th>
                  <th>Status</th>
                  <th>Updated</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$latest): ?>
                  <tr>
                    <td colspan="4" class="text-center text-muted-600">No clearance requests yet.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($offices as $i => $of): ?>
                    <?php $cls = badge_class($of['status']); ?>
                    <tr>
                      <td><?php echo $i+1; ?></td>
                      <td><?php echo htmlspecialchars(ucfirst($of['name'])); ?></td>
                      <td><span class="badge <?php echo $cls; ?>"><?php echo ucfirst($of['status']); ?></span></td>
                      <td><?php echo htmlspecialchars($of['updated_at']); ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          <hr>
          <div class="d-flex flex-wrap align-items-center justify-content-between mb-2">
            <h6 class="text-muted mb-0">Past Requests</h6>
            <form class="d-flex align-items-center gap-2" method="get">
              <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="" <?php echo $statusFilter===''?'selected':''; ?>>All</option>
                <option value="pending" <?php echo $statusFilter==='pending'?'selected':''; ?>>Pending</option>
                <option value="approved" <?php echo $statusFilter==='approved'?'selected':''; ?>>Approved</option>
                <option value="rejected" <?php echo $statusFilter==='rejected'?'selected':''; ?>>Rejected</option>
              </select>
            </form>
          </div>
          <div class="table-responsive">
            <table class="table align-middle">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Request ID</th>
                  <th>Status</th>
                  <th>Created</th>
                  <th>Updated</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($past)): ?>
                  <tr><td colspan="6" class="text-center text-muted-600">No requests found.</td></tr>
                <?php else: ?>
                  <?php foreach ($past as $i => $r): ?>
                    <?php $cls = badge_class($r['overall_status']); ?>
                    <tr>
                      <td><?php echo $offset + $i + 1; ?></td>
                      <td>#<?php echo (int)$r['id']; ?></td>
                      <td><span class="badge <?php echo $cls; ?>"><?php echo ucfirst($r['overall_status']); ?></span></td>
                      <td><?php echo htmlspecialchars($r['created_at']); ?></td>
                      <td><?php echo htmlspecialchars($r['updated_at']); ?></td>
                      <td><a class="btn btn-sm btn-outline-hu" href="student/request_details.php?id=<?php echo (int)$r['id']; ?>">Details</a></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          <nav aria-label="Pagination">
            <ul class="pagination pagination-sm justify-content-end">
              <?php
                $queryBase = '?';
                if ($statusFilter !== '') { $queryBase .= 'status=' . urlencode($statusFilter) . '&'; }
                $prev = max(1, $page-1); $next = min($totalPages, $page+1);
              ?>
              <li class="page-item <?php echo $page<=1?'disabled':''; ?>">
                <a class="page-link" href="student/student_dashboard.php<?php echo $queryBase; ?>page=<?php echo $prev; ?>">Prev</a>
              </li>
              <li class="page-item disabled"><span class="page-link">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span></li>
              <li class="page-item <?php echo $page>=$totalPages?'disabled':''; ?>">
                <a class="page-link" href="student/student_dashboard.php<?php echo $queryBase; ?>page=<?php echo $next; ?>">Next</a>
              </li>
            </ul>
          </nav>
          <div class="d-flex justify-content-end">
            <a href="student/request_clearance.php" class="btn btn-hu">Request Clearance</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
