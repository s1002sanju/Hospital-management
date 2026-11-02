<?php 
$u = current_user(); 
$title = $title ?? APP_NAME; 
$uri = $_SERVER['REQUEST_URI'] ?? '/';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="theme-color" content="#12304A">
  <meta name="description" content="<?= APP_NAME ?> - Modern, convenient, and secure healthcare appointment booking.">
  <title><?= e($title) ?> | <?= APP_NAME ?></title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/assets/css/style.css?v=<?= @filemtime(__DIR__ . '/../public/assets/css/style.css') ?: '2.0.0' ?>" rel="stylesheet">
</head>
<body class="theme-hospital">

<!-- Skip Link for Keyboard Accessibility -->
<a href="#main-content" class="sr-only">Skip to main content</a>

<header>
  <nav class="navbar" aria-label="Main Navigation">
    <div class="container">
      <a class="navbar-brand" href="/" aria-label="<?= APP_NAME ?> Home">
        <span class="brand-mark" aria-hidden="true">✚</span>
        <span><?= APP_NAME ?></span>
      </a>

      <!-- Mobile Menu Toggle Button -->
      <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="mainNav" aria-label="Toggle navigation menu">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <line x1="3" y1="12" x2="21" y2="12"></line>
          <line x1="3" y1="6" x2="21" y2="6"></line>
          <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
      </button>

      <!-- Navigation Links -->
      <div class="navbar-nav ms-auto align-items-center" id="mainNav">
        <?php if (!$u): ?>
          <a class="nav-link <?= $uri === '/' || $uri === '/index.php' ? 'active' : '' ?>" href="/">Home</a>
          <a class="nav-link <?= strpos($uri, '/book.php') !== false ? 'active' : '' ?>" href="/book.php">Book Appointment</a>
          <a class="nav-link <?= strpos($uri, '/login.php') !== false ? 'active' : '' ?>" href="/login.php">Login</a>
          <a class="btn btn-sm btn-primary ms-md-2" href="/register.php">Register</a>
        <?php elseif ($u['role'] === 'admin'): ?>
          <a class="nav-link <?= strpos($uri, '/admin/index.php') !== false || $uri === '/admin/' ? 'active' : '' ?>" href="/admin/index.php">Dashboard</a>
          <a class="nav-link <?= strpos($uri, '/admin/doctors.php') !== false ? 'active' : '' ?>" href="/admin/doctors.php">Doctors</a>
          <a class="nav-link <?= strpos($uri, '/admin/slots.php') !== false ? 'active' : '' ?>" href="/admin/slots.php">Slots</a>
          <a class="nav-link <?= strpos($uri, '/admin/appointments.php') !== false ? 'active' : '' ?>" href="/admin/appointments.php">Appointments</a>
        <?php else: ?>
          <a class="nav-link <?= $uri === '/' || $uri === '/index.php' ? 'active' : '' ?>" href="/">Home</a>
          <a class="nav-link <?= strpos($uri, '/book.php') !== false ? 'active' : '' ?>" href="/book.php">Book Appointment</a>
          <a class="nav-link <?= strpos($uri, '/my_appointments.php') !== false ? 'active' : '' ?>" href="/my_appointments.php">My Appointments</a>
        <?php endif; ?>

        <?php if ($u): ?>
          <form method="post" action="/logout.php" class="ms-md-2 d-inline"><?= csrf_field() ?>
            <button type="submit" class="btn-nav-logout" data-no-loader="true" title="Log out from <?= e($u['name']) ?>">
              <span class="d-none d-sm-inline">Sign out (<?= e($u['name']) ?>)</span>
              <span class="d-sm-none">Sign out</span>
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </nav>
</header>

<main id="main-content" class="container page-shell">
<?php if (!empty($_SESSION['flash'])): [$t, $m] = $_SESSION['flash']; unset($_SESSION['flash']); ?>
  <div class="alert alert-<?= e($t) ?>" role="alert">
    <span><?= e($m) ?></span>
  </div>
<?php endif; ?>
