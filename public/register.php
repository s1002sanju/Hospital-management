<?php 
require __DIR__ . '/../includes/bootstrap.php'; 
$title = 'Patient Registration'; 
$err = null;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_check();
    $name = trim($_POST['name'] ?? ''); 
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? ''); 
    $pass = $_POST['password'] ?? '';

    if ($name === '' || strlen($name) > 100) {
        $err = 'Enter a valid name (maximum 100 characters).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'Enter a valid email address.';
    } elseif (!preg_match('/^[0-9+\- ]{7,20}$/', $phone)) {
        $err = 'Enter a valid phone number (7 to 20 digits, spaces or hyphens).';
    } elseif (strlen($pass) < 8 || !preg_match('/[A-Za-z]/', $pass) || !preg_match('/[0-9]/', $pass)) {
        $err = 'Password needs 8+ characters with both letters and digits.';
    } else {
        try {
            db()->prepare("INSERT INTO users (name,email,phone,password_hash) VALUES (?,?,?,?)")
                ->execute([$name, $email, $phone, password_hash($pass, PASSWORD_BCRYPT)]);
            flash('success', 'Registered! Please log in.'); 
            redirect('/login.php');
        } catch (PDOException $ex) {
            $code = (string)$ex->getCode();
            $err = in_array($code, ['23000', '23505'], true) || str_starts_with($code, '23') ? 'This email address is already registered.' : 'Registration failed. Please check your details.';
        }
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
          <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
          <circle cx="9" cy="7" r="4"></circle>
          <line x1="19" y1="8" x2="19" y2="14"></line>
          <line x1="22" y1="11" x2="16" y2="11"></line>
        </svg>
      </div>
      <h2>Join CityCare</h2>
      <p>
        Register for free to book appointments with hospital specialists, manage scheduled visits, and securely view your medical reports.
      </p>
    </div>

    <!-- Registration Form Section -->
    <div class="auth-form-section">
      <h4>Create Patient Account</h4>
      <p class="auth-subtitle">Fill in your information to register as a patient.</p>

      <?php if ($err): ?>
        <div class="alert alert-danger" role="alert">
          <span><?= e($err) ?></span>
        </div>
      <?php endif; ?>

      <form method="post" novalidate aria-label="Registration Form">
        <?= csrf_field() ?>

        <div class="form-group">
          <label for="reg-name" class="form-label">Full Name</label>
          <input 
            class="form-control" 
            type="text" 
            id="reg-name" 
            name="name" 
            placeholder="e.g. John Doe" 
            value="<?= e($_POST['name'] ?? '') ?>" 
            required 
            maxlength="100"
            autocomplete="name"
            autofocus
          >
        </div>

        <div class="form-group">
          <label for="reg-email" class="form-label">Email Address</label>
          <input 
            class="form-control" 
            type="email" 
            id="reg-email" 
            name="email" 
            placeholder="e.g. name@example.com" 
            value="<?= e($_POST['email'] ?? '') ?>" 
            required 
            autocomplete="email"
          >
          <div class="form-helper">Your appointment confirmations and reminders will be sent here.</div>
        </div>

        <div class="form-group">
          <label for="reg-phone" class="form-label">Phone Number</label>
          <input 
            class="form-control" 
            type="tel" 
            id="reg-phone" 
            name="phone" 
            placeholder="e.g. +91 9876543210" 
            value="<?= e($_POST['phone'] ?? '') ?>" 
            required 
            pattern="[0-9+\- ]{7,20}"
            autocomplete="tel"
          >
        </div>

        <div class="form-group">
          <label for="reg-password" class="form-label">Password</label>
          <div class="password-wrapper">
            <input 
              class="form-control" 
              type="password" 
              id="reg-password" 
              name="password" 
              placeholder="Minimum 8 characters (letters & digits)" 
              required 
              minlength="8"
              autocomplete="new-password"
            >
            <button 
              type="button" 
              class="password-toggle" 
              data-toggle-password="#reg-password" 
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
          <div class="form-helper">Requires at least 8 characters, containing both letters and numbers.</div>
        </div>

        <button type="submit" class="btn btn-primary w-100 btn-lg mt-3">
          Create Account
        </button>
      </form>

      <div class="auth-footer-link">
        Already have an account? <a href="/login.php">Sign in</a>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
