<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../includes/auth_student.php';
require __DIR__ . '/../includes/db.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
  $comments = trim($_POST['comments'] ?? '');
  $selectedOffices = array_map('intval', $_POST['offices'] ?? []);
  $user = $_SESSION['user'] ?? null;
  if ($user && ($user['role'] ?? '') === 'student') {
    $studentId = (int)($user['id'] ?? 0);
    if ($studentId > 0) {
      // Create clearance request
      $ins = $conn->prepare('INSERT INTO clearance_requests (student_id, comments, overall_status) VALUES (?,?,"pending")');
      $ins->bind_param('is', $studentId, $comments);
      if ($ins->execute()) {
        $requestId = $conn->insert_id;
        // Determine which offices to seed
        $officeIds = [];
        if (!empty($selectedOffices)) {
          // Validate IDs exist
          $idsCsv = implode(',', array_fill(0, count($selectedOffices), '?'));
          $types = str_repeat('i', count($selectedOffices));
          $stmt = $conn->prepare("SELECT id FROM offices WHERE id IN ($idsCsv)");
          $stmt->bind_param($types, ...$selectedOffices);
          $stmt->execute();
          $res = $stmt->get_result();
          while ($row = $res->fetch_assoc()) { $officeIds[] = (int)$row['id']; }
        }
        if (empty($officeIds)) {
          // Fallback to all offices if none selected or invalid
          $resAll = $conn->query('SELECT id FROM offices');
          while ($row = $resAll->fetch_assoc()) { $officeIds[] = (int)$row['id']; }
        }
        // Seed office_clearance for chosen offices
        foreach ($officeIds as $officeId) {
          $stmt = $conn->prepare('INSERT INTO office_clearance (request_id, office_id, status) VALUES (?,?,"pending")');
          $stmt->bind_param('ii', $requestId, $officeId);
          $stmt->execute();
        }
        header('Location: ../student/student_dashboard.php?requested=1');
        exit;
      }
    }
  }
}
?>
<?php include '../includes/header.php'; ?>
<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-md-8">
      <div class="card">
        <div class="card-body">
          <h5 class="mb-3">Request Clearance</h5>
          <form action="student/request_clearance.php" method="post" class="needs-validation" novalidate>
            <div class="mb-3">
              <label class="form-label d-block">Select Offices</label>
              <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" id="selectAllOffices">
                <label class="form-check-label" for="selectAllOffices">Select All</label>
              </div>
<?php
// Fetch offices for selection list
$officeList = [];
if ($res = $conn->query('SELECT id, name FROM offices ORDER BY name')) {
  while ($row = $res->fetch_assoc()) { $officeList[] = $row; }
}
foreach ($officeList as $o):
  $oid = (int)$o['id'];
  $oname = htmlspecialchars(ucfirst($o['name']), ENT_QUOTES);
?>
              <div class="form-check">
                <input class="form-check-input office-check" type="checkbox" name="offices[]" value="<?php echo $oid; ?>" id="office_<?php echo $oid; ?>">
                <label class="form-check-label" for="office_<?php echo $oid; ?>"><?php echo $oname; ?></label>
              </div>
<?php endforeach; ?>
              <small class="text-muted">Leave unselected to request clearance from all offices.</small>
            </div>
            <div class="mb-3">
              <label class="form-label">Comments (optional)</label>
              <textarea name="comments" class="form-control" rows="4" placeholder="Any additional information..."></textarea>
            </div>
            <button class="btn btn-hu">Submit Request</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
<script>
  (function(){
    const selectAll = document.getElementById('selectAllOffices');
    const checks = document.querySelectorAll('.office-check');
    if (selectAll && checks.length) {
      selectAll.addEventListener('change', function(){
        checks.forEach(c => c.checked = selectAll.checked);
      });
    }
  })();
</script>
