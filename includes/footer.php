</main>

<footer class="footer">
  <div class="container footer-main">
    <div class="footer-grid">
      <div>
        <div class="footer-brand">
          <span class="brand-mark" aria-hidden="true">✚</span>
          <span><?= APP_NAME ?></span>
        </div>
        <p class="footer-desc">
          Providing modern, reliable healthcare scheduling for patients and hospital staff. Convenient appointment booking, automated notifications, and secure health records.
        </p>
      </div>

      <div>
        <h6 class="footer-heading">Quick Links</h6>
        <ul class="footer-links">
          <li><a href="/">Home</a></li>
          <li><a href="/book.php">Book Appointment</a></li>
          <?php $footerUser = current_user(); ?>
          <?php if (!$footerUser): ?>
            <li><a href="/login.php">Patient Login</a></li>
            <li><a href="/register.php">Create Account</a></li>
          <?php elseif ($footerUser['role'] === 'admin'): ?>
            <li><a href="/admin/index.php">Admin Dashboard</a></li>
            <li><a href="/admin/appointments.php">All Appointments</a></li>
          <?php else: ?>
            <li><a href="/my_appointments.php">My Appointments</a></li>
          <?php endif; ?>
        </ul>
      </div>

      <div>
        <h6 class="footer-heading">Patient Care</h6>
        <ul class="footer-links">
          <li><a href="/book.php">Specialist Availability</a></li>
          <li><a href="/#how-it-works">How It Works</a></li>
          <li><a href="/#doctors">Our Medical Team</a></li>
        </ul>
      </div>
    </div>
  </div>

  <div class="footer-bottom">
    <div class="container">
      &copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved. Professional healthcare scheduling system.
    </div>
  </div>
</footer>

<script src="/assets/js/main.js?v=<?= @filemtime(__DIR__ . '/../public/assets/js/main.js') ?: '2.0.0' ?>"></script>
</body>
</html>
