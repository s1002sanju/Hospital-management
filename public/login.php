<?php 
require __DIR__ . '/../includes/bootstrap.php'; 
$title = 'Patient & Staff Login'; 
$err = null;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_check();
    $fails = $_SESSION['fails'] ?? ['n' => 0, 't' => time()];
    if ($fails['n'] >= 5 && time() - $fails['t'] < 300) { 
        $err = 'Too many attempts. Try again in 5 minutes.'; 
    } else {
        $st = db()->prepare("SELECT * FROM users WHERE email=?"); 
        $st->execute([strtolower(trim($_POST['email'] ?? ''))]);
        $u = $st->fetch();
        if ($u && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
            session_regenerate_id(true); 
            unset($_SESSION['fails']);
            $_SESSION['user'] = [
                'id' => (int)$u['id'], 
                'name' => $u['name'], 
                'email' => $u['email'], 
                'role' => $u['role']
            ];
            redirect($u['role'] === 'admin' ? '/admin/index.php' : '/book.php');
        }
        $_SESSION['fails'] = ['n' => ($fails['n'] >= 5 ? 1 : $fails['n'] + 1), 't' => time()];
        $err = 'Invalid email or password.';
    }
}

require __DIR__ . '/../includes/header.php'; 
?>

<div class="auth-page">
  <div class="auth-container">
    <!-- Visual Brand Panel -->
    <div class="auth-visual" aria-hidden="true">
      <div class="auth-visual-icon">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 2v20M2 12h20"></path>
        </svg>
      </div>
      <h2>CityCare Portal</h2>
      <p>
        Sign in to schedule doctor visits, check appointment statuses, and access your consultation records securely.
      </p>
    </div>

    <!-- Login Form Section -->
    <div class="auth-form-section">
      <h4>Welcome Back</h4>
      <p class="auth-subtitle">Sign in with your registered email and password.</p>

      <?php if ($err): ?>
        <div class="alert alert-danger" role="alert">
          <span><?= e($err) ?></span>
        </div>
      <?php endif; ?>

      <form method="post" novalidate aria-label="Login Form">
        <?= csrf_field() ?>

        <div class="form-group">
          <label for="login-email" class="form-label">Email Address</label>
          <input 
            class="form-control" 
            type="email" 
            id="login-email" 
            name="email" 
            placeholder="e.g. yourname@example.com" 
            value="<?= e($_POST['email'] ?? '') ?>" 
            required 
            autocomplete="email"
            autofocus
          >
        </div>

        <div class="form-group">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <label for="login-password" class="form-label mb-0">Password</label>
          </div>
          <div class="password-wrapper">
            <input 
              class="form-control" 
              type="password" 
              id="login-password" 
              name="password" 
              placeholder="Enter your password" 
              required 
              autocomplete="current-password"
            >
            <button 
              type="button" 
              class="password-toggle" 
              data-toggle-password="#login-password" 
              aria-label="Show password"
            >
              <span class="pw-icon" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
              </span>
            </button>
          </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 btn-lg mt-3">
          Sign In
        </button>
      </form>

      <div class="auth-footer-link">
        Don't have an account yet? <a href="/register.php">Create an account</a>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
