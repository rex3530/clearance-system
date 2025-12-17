<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . '/includes/db.php';

$error = '';
$activeTab = 'student';

function password_matches($input, $stored) {
  if (!$stored) return false;
  if (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$argon2')) {
    return password_verify($input, $stored);
  }
  return hash_equals($stored, $input);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $role = $_POST['role'] ?? '';
  $activeTab = $role ?: 'student';

  if ($role === 'student') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($email && $password) {
      $stmt = $conn->prepare("SELECT id, name, email, password_hash FROM students WHERE email = ? LIMIT 1");
      $stmt->bind_param('s', $email);
      $stmt->execute();
      $res = $stmt->get_result();
      if ($row = $res->fetch_assoc()) {
        if (password_matches($password, $row['password_hash'])) {
          $_SESSION['user'] = [
            'id' => (int)$row['id'],
            'role' => 'student',
            'name' => $row['name'] ?? $row['email'],
            'email' => $row['email'],
          ];
          header('Location: student/student_dashboard.php');
          exit;
        }
      }
      $error = 'Invalid student email or password.';
    } else {
      $error = 'Please enter email and password.';
    }
  } elseif ($role === 'admin') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username && $password) {
      $stmt = $conn->prepare("SELECT id, username, password_hash FROM admin_users WHERE username = ? LIMIT 1");
      $stmt->bind_param('s', $username);
      $stmt->execute();
      $res = $stmt->get_result();
      if ($row = $res->fetch_assoc()) {
        if (password_matches($password, $row['password_hash'])) {
          $_SESSION['user'] = [
            'id' => (int)$row['id'],
            'role' => 'admin',
            'username' => $row['username'],
          ];
          header('Location: admin/admin_dashboard.php');
          exit;
        }
      }
      $error = 'Invalid admin credentials.';
    } else {
      $error = 'Please enter username and password.';
    }
  } elseif ($role === 'office') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username && $password) {
      // Try office_users if exists; otherwise block with helpful error.
      $officeUsersExists = false;
      if ($res = $conn->query("SHOW TABLES LIKE 'office_users'")) {
        if ($res->num_rows > 0) { $officeUsersExists = true; }
      }
      if ($officeUsersExists) {
        $stmt = $conn->prepare("SELECT ou.id, ou.username, ou.password_hash, o.id as office_id, o.name as office_name
                                FROM office_users ou JOIN offices o ON ou.office_id = o.id
                                WHERE ou.username = ? LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $r = $stmt->get_result();
        if ($row = $r->fetch_assoc()) {
          if (password_matches($password, $row['password_hash'])) {
            $_SESSION['user'] = [
              'id' => (int)$row['id'],
              'role' => 'office',
              'username' => $row['username'],
              'office_id' => (int)$row['office_id'],
              'office_name' => $row['office_name'],
            ];
            header('Location: office/office_dashboard.php');
            exit;
          }
        }
        $error = 'Invalid office credentials.';
      } else {
        $error = 'Office login is not configured. Create an office_users table or use admin/student.';
      }
    } else {
      $error = 'Please enter username and password.';
    }
  }
}
?>
<?php include './includes/header.php'; ?>
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card shadow-soft">
        <div class="card-body p-4">
          <h4 class="mb-3 text-center">Login</h4>
          <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
          <?php endif; ?>
          <ul class="nav nav-pills justify-content-center mb-4" id="loginTabs" role="tablist">
            <li class="nav-item"><button class="nav-link <?php echo $activeTab==='student'?'active':''; ?>" data-bs-toggle="pill" data-bs-target="#student" type="button">Student</button></li>
            <li class="nav-item"><button class="nav-link <?php echo $activeTab==='office'?'active':''; ?>" data-bs-toggle="pill" data-bs-target="#office" type="button">Office</button></li>
            <li class="nav-item"><button class="nav-link <?php echo $activeTab==='admin'?'active':''; ?>" data-bs-toggle="pill" data-bs-target="#admin" type="button">Admin</button></li>
          </ul>
          <div class="tab-content">
            <div class="tab-pane fade <?php echo $activeTab==='student'?'show active':''; ?>" id="student">
              <form action="login.php" method="post" novalidate class="needs-validation">
                <div class="mb-3">
                  <label class="form-label">Email</label>
                  <input type="email" name="email" class="form-control" required>
                  <div class="invalid-feedback">Please enter a valid email.</div>
                </div>
                <div class="mb-3">
                  <label class="form-label">Password</label>
                  <input type="password" name="password" class="form-control" required>
                </div>
                <input type="hidden" name="role" value="student">
                <button class="btn btn-hu w-100">Login as Student</button>
              </form>
            </div>
            <div class="tab-pane fade <?php echo $activeTab==='office'?'show active':''; ?>" id="office">
              <form action="login.php" method="post" novalidate class="needs-validation">
                <div class="mb-3">
                  <label class="form-label">Username</label>
                  <input type="text" name="username" class="form-control" required>
                </div>
                <div class="mb-3">
                  <label class="form-label">Password</label>
                  <input type="password" name="password" class="form-control" required>
                </div>
                <input type="hidden" name="role" value="office">
                <button class="btn btn-hu w-100">Login as Office</button>
              </form>
            </div>
            <div class="tab-pane fade <?php echo $activeTab==='admin'?'show active':''; ?>" id="admin">
              <form action="login.php" method="post" novalidate class="needs-validation">
                <div class="mb-3">
                  <label class="form-label">Username</label>
                  <input type="text" name="username" class="form-control" required>
                </div>
                <div class="mb-3">
                  <label class="form-label">Password</label>
                  <input type="password" name="password" class="form-control" required>
                </div>
                <input type="hidden" name="role" value="admin">
                <button class="btn btn-hu w-100">Login as Admin</button>
              </form>
            </div>
          </div>
          <p class="mt-3 text-center text-muted-600">No account? <a href="register.php">Register</a></p>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include './includes/footer.php'; ?>
