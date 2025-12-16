<?php include '../includes/auth_admin.php'; ?>
<?php include '../includes/header.php'; ?>
<?php require __DIR__ . '/../includes/db.php'; ?>
<?php
// Filters
$status = isset($_GET['status']) ? strtolower(trim($_GET['status'])) : '';
if (!in_array($status, ['', 'pending','approved','rejected'], true)) { $status=''; }
$officeId = (int)($_GET['office_id'] ?? 0);

// Quick counts (by distinct request id)
if ($officeId > 0) {
  // Count distinct requests for a specific office using office_clearance
  $stmtT = $conn->prepare('SELECT COUNT(DISTINCT request_id) c FROM office_clearance WHERE office_id = ?');
  $stmtT->bind_param('i', $officeId);
  $stmtT->execute();
  $total = (int)($stmtT->get_result()->fetch_assoc()['c'] ?? 0);

  $stmtA = $conn->prepare("SELECT COUNT(DISTINCT request_id) c FROM office_clearance WHERE office_id = ? AND status='approved'");
  $stmtA->bind_param('i', $officeId);
  $stmtA->execute();
  $approved = (int)($stmtA->get_result()->fetch_assoc()['c'] ?? 0);

  $stmtP = $conn->prepare("SELECT COUNT(DISTINCT request_id) c FROM office_clearance WHERE office_id = ? AND status='pending'");
  $stmtP->bind_param('i', $officeId);
  $stmtP->execute();
  $pending = (int)($stmtP->get_result()->fetch_assoc()['c'] ?? 0);

  $stmtR = $conn->prepare("SELECT COUNT(DISTINCT request_id) c FROM office_clearance WHERE office_id = ? AND status='rejected'");
  $stmtR->bind_param('i', $officeId);
  $stmtR->execute();
  $rejected = (int)($stmtR->get_result()->fetch_assoc()['c'] ?? 0);
} else {
  // Global counts by request (overall_status)
  $qTotal = $conn->query('SELECT COUNT(*) c FROM clearance_requests');
  $total = (int)($qTotal ? $qTotal->fetch_assoc()['c'] : 0);
  $qApproved = $conn->query("SELECT COUNT(*) c FROM clearance_requests WHERE overall_status='approved'");
  $approved = (int)($qApproved ? $qApproved->fetch_assoc()['c'] : 0);
  $qPending = $conn->query("SELECT COUNT(*) c FROM clearance_requests WHERE overall_status='pending'");
  $pending = (int)($qPending ? $qPending->fetch_assoc()['c'] : 0);
  $qRejected = $conn->query("SELECT COUNT(*) c FROM clearance_requests WHERE overall_status='rejected'");
  $rejected = (int)($qRejected ? $qRejected->fetch_assoc()['c'] : 0);
}

// Offices for filter
$offices = [];
if ($res = $conn->query('SELECT id, name FROM offices ORDER BY name')) {
  while ($row = $res->fetch_assoc()) { $offices[] = $row; }
}

// Data table: per-office status
$rows = [];
$sql = "SELECT cr.id AS request_id, cr.updated_at AS req_updated,
               s.name AS student_name,
               o.name AS office_name,
               oc.status AS office_status,
               oc.updated_at AS office_updated
        FROM office_clearance oc
        JOIN clearance_requests cr ON cr.id = oc.request_id
        JOIN students s ON s.id = cr.student_id
        JOIN offices o ON o.id = oc.office_id";
$where = [];
$params = [];
$types = '';
if ($officeId > 0) { $where[] = 'oc.office_id = ?'; $params[] = $officeId; $types .= 'i'; }
if ($status !== '') { $where[] = 'oc.status = ?'; $params[] = $status; $types .= 's'; }
if (!empty($where)) { $sql .= ' WHERE ' . implode(' AND ', $where); }
$sql .= ' ORDER BY cr.updated_at DESC';

if ($types === '') {
  $res = $conn->query($sql);
  if ($res) { while ($r = $res->fetch_assoc()) { $rows[] = $r; } }
} else {
  $stmt = $conn->prepare($sql);
  $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($r = $res->fetch_assoc()) { $rows[] = $r; }
}

// Group rows by office
$grouped = [];
foreach ($rows as $r) {
  $key = $r['office_name'];
  if (!isset($grouped[$key])) $grouped[$key] = [];
  $grouped[$key][] = $r;
}
?>
<div class="container py-4">
  <div class="row g-3">
    <div class="col-md-4">
      <div class="card card-hover">
        <div class="card-body">
          <h6 class="text-muted">Total Requests</h6>
          <h3 class="mb-0"><?php echo $total; ?></h3>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card card-hover">
        <div class="card-body">
          <h6 class="text-muted">Approved</h6>
          <h3 class="mb-0 text-success"><?php echo $approved; ?></h3>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card card-hover">
        <div class="card-body">
          <h6 class="text-muted">Pending</h6>
          <h3 class="mb-0 text-warning"><?php echo $pending; ?></h3>
        </div>
      </div>
    </div>
  </div>
  <div class="card mt-4">
    <div class="card-body">
      <?php if (!empty($_GET['deleted'])): ?>
        <div class="alert alert-success" role="alert">Request deleted.</div>
      <?php endif; ?>
      <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
        <h5 class="mb-0">All Clearance Requests</h5>
        <form class="d-flex gap-2" method="get">
          <select name="office_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="0" <?php echo $officeId===0?'selected':''; ?>>All Offices</option>
            <?php foreach ($offices as $o): ?>
              <option value="<?php echo (int)$o['id']; ?>" <?php echo $officeId===(int)$o['id']?'selected':''; ?>
              ><?php echo htmlspecialchars(ucfirst($o['name'])); ?></option>
            <?php endforeach; ?>
          </select>
          <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="" <?php echo $status===''?'selected':''; ?>>All Statuses</option>
            <option value="pending" <?php echo $status==='pending'?'selected':''; ?>>Pending</option>
            <option value="approved" <?php echo $status==='approved'?'selected':''; ?>>Approved</option>
            <option value="rejected" <?php echo $status==='rejected'?'selected':''; ?>>Rejected</option>
          </select>
        </form>
      </div>
      <div class="accordion" id="officeAcc">
        <?php if (empty($grouped)): ?>
          <div class="text-center text-muted-600">No records found.</div>
        <?php else: $idx=0; foreach ($grouped as $officeName => $items): $idx++; ?>
          <div class="accordion-item">
            <h2 class="accordion-header" id="heading<?php echo $idx; ?>">
              <button class="accordion-button <?php echo $idx>1?'collapsed':''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $idx; ?>" aria-expanded="<?php echo $idx===1?'true':'false'; ?>" aria-controls="collapse<?php echo $idx; ?>">
                <?php echo htmlspecialchars(ucfirst($officeName)); ?>
                <span class="badge bg-secondary ms-2"><?php echo count($items); ?></span>
              </button>
            </h2>
            <div id="collapse<?php echo $idx; ?>" class="accordion-collapse collapse <?php echo $idx===1?'show':''; ?>" aria-labelledby="heading<?php echo $idx; ?>" data-bs-parent="#officeAcc">
              <div class="accordion-body">
                <div class="table-responsive">
                  <table class="table align-middle">
                    <thead>
                      <tr>
                        <th>#</th>
                        <th>Request ID</th>
                        <th>Student</th>
                        <th>Status</th>
                        <th>Updated</th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($items as $i => $r): ?>
                        <?php $s = $r['office_status']; $cls = $s==='approved'?'badge-approved':($s==='rejected'?'badge-rejected':'badge-pending'); ?>
                        <tr>
                          <td><?php echo $i+1; ?></td>
                          <td>#<?php echo (int)$r['request_id']; ?></td>
                          <td><?php echo htmlspecialchars($r['student_name']); ?></td>
                          <td><span class="badge <?php echo $cls; ?>"><?php echo ucfirst($s); ?></span></td>
                          <td><?php echo htmlspecialchars($r['office_updated']); ?></td>
                          <td>
                            <a class="btn btn-sm btn-outline-hu me-2" href="admin/view_request.php?id=<?php echo (int)$r['request_id']; ?>">Details</a>
                            <form action="admin/delete_request.php" method="post" class="d-inline">
                              <input type="hidden" name="request_id" value="<?php echo (int)$r['request_id']; ?>">
                              <button class="btn btn-sm btn-danger" data-confirm="Delete this request? This cannot be undone.">Delete</button>
                            </form>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
