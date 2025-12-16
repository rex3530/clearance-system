<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
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
</head>
<body>
<nav class="navbar navbar-expand-lg">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="index.html">
      <img src="images/HULOGO.png" alt="HU Logo" style="height:36px;width:auto;">
      <span class="fw-semibold">HU Clearance</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="student/student_dashboard.php">Student</a></li>
        <li class="nav-item"><a class="nav-link" href="office/office_dashboard.php">Office</a></li>
        <li class="nav-item"><a class="nav-link" href="admin/admin_dashboard.php">Admin</a></li>
        <?php if (!empty($_SESSION['user'])): ?>
          <li class="nav-item ms-lg-3">
            <a class="btn btn-outline-hu" href="logout.php">Logout</a>
          </li>
        <?php else: ?>
          <li class="nav-item ms-lg-3">
            <a class="btn btn-hu" href="login.php">Login</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
