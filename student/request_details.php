<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../includes/auth_student.php';
require __DIR__ . '/../includes/db.php';

$studentId = (int)($_SESSION['user']['id'] ?? 0);
$rid = (int)($_GET['id'] ?? 0);

// Fetch request header (ensure belongs to student)
$stmt = $conn->prepare('SELECT id, overall_status, comments, created_at, updated_at FROM clearance_requests WHERE id = ? AND student_id = ? LIMIT 1');
$stmt->bind_param('ii', $rid, $studentId);
$stmt->execute();
$req = $stmt->get_result()->fetch_assoc();

if (!$req) {
  header('Location: student_dashboard.php');
  exit;
}

$offices = [];
$q = $conn->prepare('SELECT o.name, oc.status, oc.comment, oc.updated_at FROM office_clearance oc JOIN offices o ON o.id = oc.office_id WHERE oc.request_id = ? ORDER BY o.name');
$q->bind_param('i', $rid);
$q->execute();
$rs = $q->get_result();
while ($row = $rs->fetch_assoc()) { $offices[] = $row; }

function badge_class($s){
  if ($s==='approved') return 'badge-approved';
  if ($s==='rejected') return 'badge-rejected';
  return 'badge-pending';
}
?>
<?php include '../includes/header.php'; ?>
<div class="container py-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="mb-0">Request Details #<?php echo (int)$req['id']; ?></h5>
    <a class="btn btn-sm btn-outline-hu" href="student/student_dashboard.php">Back</a>
  </div>

  <div class="row g-3">
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <div class="text-muted">Overall Status</div>
          <?php $cls = badge_class($req['overall_status']); ?>
          <div class="mt-2">
            <span class="badge <?php echo $cls; ?> rounded-pill px-3 py-2"><?php echo ucfirst($req['overall_status']); ?></span>
          </div>
          <div class="small text-muted mt-2">Created: <?php echo htmlspecialchars($req['created_at']); ?></div>
          <div class="small text-muted">Updated: <?php echo htmlspecialchars($req['updated_at']); ?></div>
          <?php if (!empty($req['comments'])): ?>
            <hr>
            <div class="text-muted">Your comments</div>
            <p class="mb-0"><?php echo nl2br(htmlspecialchars($req['comments'])); ?></p>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-md-8">
      <div class="card">
        <div class="card-body">
          <h6 class="text-muted mb-2">Office Approvals</h6>
          <div class="table-responsive">
            <table class="table align-middle">
              <thead>
                <tr>
                  <th>Office</th>
                  <th>Status</th>
                  <th>Comment</th>
                  <th>Updated</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($offices as $o): ?>
                  <?php $cls = badge_class($o['status']); ?>
                  <tr>
                    <td><?php echo htmlspecialchars(ucfirst($o['name'])); ?></td>
                    <td><span class="badge <?php echo $cls; ?>"><?php echo ucfirst($o['status']); ?></span></td>
                    <td><?php echo htmlspecialchars($o['comment'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($o['updated_at']); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
