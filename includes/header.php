<?php
if (session_status() === PHP_SESSION_NONE) session_start();
// Disable caching to avoid stale headers (e.g., showing Logout after session was cleared)
if (!headers_sent()) {
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  header('Cache-Control: post-check=0, pre-check=0', false);
  header('Pragma: no-cache');
  header('Expires: 0');
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Haramaya University Clearance System</title>
<?php
  $script = $_SERVER['SCRIPT_NAME'] ?? '/clearance_system/';
  $needle = '/clearance_system/';
  $pos = strpos($script, $needle);
  $base = ($pos !== false) ? substr($script, 0, $pos + strlen($needle)) : $needle;
?>
  <base href="<?php echo htmlspecialchars($base, ENT_QUOTES); ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="icon" href="images/HULOGO.png">
  <link rel="stylesheet" href="assets/css/style.css">
  <?php $studentOnly = (getenv('STUDENT_ONLY') === '1'); ?>
</head>
<body>
<nav class="navbar navbar-expand-lg">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
      <img src="images/HULOGO.png" alt="HU Logo" style="height:36px;width:auto;">
      <span class="fw-semibold">HU Clearance</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <?php if (!empty($_SESSION['user'])): ?>
          <?php if (($_SESSION['user']['role'] ?? '') === 'admin'): ?>
            <li class="nav-item me-lg-2">
              <a class="btn btn-outline-hu" href="admin/office_users.php">Manage Office Users</a>
            </li>
          <?php endif; ?>
          <li class="nav-item ms-lg-3">
            <a class="btn btn-outline-hu" href="logout.php">Logout</a>
          </li>
        <?php else: ?>
          <?php if ($studentOnly): ?>
            <li class="nav-item ms-lg-3">
              <a class="btn btn-hu" href="login.php">Login</a>
            </li>
          <?php else: ?>
            <li class="nav-item dropdown ms-lg-3">
              <a class="btn btn-hu dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                Login
              </a>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="login.php">Student Login</a></li>
                <li><a class="dropdown-item" href="login.php?role=office">Office Login</a></li>
                <li><a class="dropdown-item" href="login.php?role=admin">Admin Login</a></li>
              </ul>
            </li>
          <?php endif; ?>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
