<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../includes/auth_admin.php';
require __DIR__ . '/../includes/db.php';

$alert = '';
$alertType = 'success';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
  $uid = (int)($_POST['user_id'] ?? 0);
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($uid > 0 && $username !== '') {
    // Ensure username unique (excluding current user)
    $stmt = $conn->prepare('SELECT id FROM office_users WHERE username = ? AND id <> ? LIMIT 1');
    $stmt->bind_param('si', $username, $uid);
    $stmt->execute();
    $exists = (bool)$stmt->get_result()->fetch_assoc();
    if ($exists) {
      $alert = 'Username already taken.';
      $alertType = 'danger';
    } else {
      if ($password !== '') {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $up = $conn->prepare('UPDATE office_users SET username = ?, password_hash = ? WHERE id = ?');
        $up->bind_param('ssi', $username, $hash, $uid);
      } else {
        $up = $conn->prepare('UPDATE office_users SET username = ? WHERE id = ?');
        $up->bind_param('si', $username, $uid);
      }
      if ($up->execute()) {
        $alert = 'Office user updated.';
        $alertType = 'success';
      } else {
        $alert = 'Update failed.';
        $alertType = 'danger';
      }
    }
  } else {
    $alert = 'Invalid input.';
    $alertType = 'danger';
  }
}

// Load office users list
$rows = [];
$sql = 'SELECT ou.id, ou.username, o.name AS office_name FROM office_users ou JOIN offices o ON o.id = ou.office_id ORDER BY o.name, ou.username';
if ($res = $conn->query($sql)) {
  while ($r = $res->fetch_assoc()) { $rows[] = $r; }
}
?>
<?php include '../includes/header.php'; ?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Manage Office Users</h5>
    <a class="btn btn-outline-hu" href="admin/admin_dashboard.php">Back to Dashboard</a>
  </div>
  <?php if ($alert !== ''): ?>
    <div class="alert alert-<?php echo $alertType; ?>" role="alert"><?php echo htmlspecialchars($alert); ?></div>
  <?php endif; ?>
  <div class="card">
    <div class="card-body">
      <?php if (empty($rows)): ?>
        <div class="text-muted-600">No office users found.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr>
                <th>Office</th>
                <th>Username</th>
                <th style="width:320px;">Set New Password (optional)</th>
                <th style="width:140px;"></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $r): ?>
                <tr>
                  <td><?php echo htmlspecialchars(ucfirst($r['office_name'])); ?></td>
                  <td>
                    <form action="admin/office_users.php" method="post" class="row g-2 align-items-center">
                      <input type="hidden" name="user_id" value="<?php echo (int)$r['id']; ?>">
                      <div class="col-md-5">
                        <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($r['username']); ?>" required>
                      </div>
                      <div class="col-md-5">
                        <input type="password" name="password" class="form-control" placeholder="Leave blank to keep same">
                      </div>
                      <div class="col-md-2">
                        <button class="btn btn-hu w-100">Save</button>
                      </div>
                    </form>
                  </td>
                  <td class="d-none"></td>
                  <td class="d-none"></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
