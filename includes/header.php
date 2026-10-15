<?php $u = current_user(); $title = $title ?? APP_NAME; ?>
<!doctype html><html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title) ?> | <?= APP_NAME ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body class="bg-light">
<nav class="navbar navbar-expand-md navbar-dark bg-primary mb-4"><div class="container">
  <a class="navbar-brand" href="/">&#10010; <?= APP_NAME ?></a>
  <div class="navbar-nav ms-auto align-items-center">
  <?php if (!$u): ?>
    <a class="nav-link" href="/login.php">Login</a><a class="nav-link" href="/register.php">Register</a>
  <?php elseif ($u['role'] === 'admin'): ?>
    <a class="nav-link" href="/admin/index.php">Dashboard</a><a class="nav-link" href="/admin/doctors.php">Doctors</a>
    <a class="nav-link" href="/admin/slots.php">Slots</a><a class="nav-link" href="/admin/appointments.php">Appointments</a>
  <?php else: ?>
    <a class="nav-link" href="/book.php">Book</a><a class="nav-link" href="/my_appointments.php">My Appointments</a>
  <?php endif; ?>
  <?php if ($u): ?><form method="post" action="/logout.php" class="ms-2"><?= csrf_field() ?>
    <button class="btn btn-sm btn-light">Logout (<?= e($u['name']) ?>)</button></form><?php endif; ?>
  </div></div></nav>
<div class="container">
<?php if (!empty($_SESSION['flash'])): [$t, $m] = $_SESSION['flash']; unset($_SESSION['flash']); ?>
  <div class="alert alert-<?= e($t) ?>"><?= e($m) ?></div>
<?php endif; ?>
