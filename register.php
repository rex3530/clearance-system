<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . '/includes/db.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $student_id = trim($_POST['student_id'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $department = trim($_POST['department'] ?? '');
  $password = $_POST['password'] ?? '';
  $confirm = $_POST['confirm_password'] ?? '';

  if (!$name || !$student_id || !$email || !$department || !$password || !$confirm) {
    $error = 'Please fill in all required fields.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Please enter a valid email address.';
  } elseif ($password !== $confirm) {
    $error = 'Passwords do not match.';
  } else {
    // Check duplicates
    $stmt = $conn->prepare('SELECT id FROM students WHERE email = ? OR student_id = ? LIMIT 1');
    $stmt->bind_param('ss', $email, $student_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->fetch_assoc()) {
      $error = 'An account with this email or student ID already exists.';
    } else {
      $hash = password_hash($password, PASSWORD_BCRYPT);
      $ins = $conn->prepare('INSERT INTO students (student_id, name, email, department, password_hash) VALUES (?,?,?,?,?)');
      $ins->bind_param('sssss', $student_id, $name, $email, $department, $hash);
      if ($ins->execute()) {
        $success = 'Registration successful. You can now log in.';
      } else {
        $error = 'Registration failed. Please try again later.';
      }
    }
  }
}
?>
<?php include './includes/header.php'; ?>
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-7">
      <div class="card shadow-soft">
        <div class="card-body p-4">
          <h4 class="mb-3 text-center">Student Registration</h4>
          <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
          <?php endif; ?>
          <?php if (!empty($success)): ?>
            <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($success); ?> <a href="login.php" class="alert-link">Login</a></div>
          <?php endif; ?>
          <form action="register.php" method="post" novalidate class="needs-validation">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Student ID</label>
                <input type="text" name="student_id" class="form-control" value="<?php echo htmlspecialchars($_POST['student_id'] ?? ''); ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Department</label>
                <input type="text" name="department" class="form-control" value="<?php echo htmlspecialchars($_POST['department'] ?? ''); ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
              </div>
            </div>
            <button class="btn btn-hu w-100 mt-3">Create Account</button>
          </form>
          <p class="mt-3 text-center text-muted-600">Already have an account? <a href="login.php">Login</a></p>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include './includes/footer.php'; ?>
