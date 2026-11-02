<?php 
require __DIR__ . '/../includes/bootstrap.php'; 
$title = 'Home';

$pdo = db();
$docs = $pdo->query("SELECT d.id, d.name, d.qualification, d.fee, dp.name AS dept FROM doctors d JOIN departments dp ON dp.id=d.department_id WHERE d.is_active=true ORDER BY dp.name, d.name")->fetchAll();
$depts = $pdo->query("SELECT dp.id, dp.name, COUNT(d.id) AS doctor_count FROM departments dp LEFT JOIN doctors d ON d.department_id=dp.id AND d.is_active=true GROUP BY dp.id, dp.name ORDER BY dp.name")->fetchAll();

require __DIR__ . '/../includes/header.php'; 
?>

<!-- Hero Section -->
<section class="hero-section" aria-labelledby="hero-title">
  <div class="row align-items-center g-5">
    <div class="col-lg-7">
      <div class="hero-trust-badge">
        <span>Your Health, Our Priority</span>
      </div>
      <h1 id="hero-title">Better Healthcare Starts With the Right Appointment.</h1>
      <p class="lead">
        Find doctors, view real-time slot availability, and schedule your consultation in seconds. Receive instant confirmations and timely reminders before every visit.
      </p>
      
      <div class="hero-actions">
        <a class="btn btn-primary btn-lg" href="/book.php">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
          Book an Appointment
        </a>
        <a class="btn btn-outline-light btn-lg" href="#departments">
          Explore Our Services
        </a>
      </div>

      <div class="hero-features">
        <div class="hero-feature">
          <span>Live Slot Availability</span>
        </div>
        <div class="hero-feature">
          <span>Instant Email Confirmation</span>
        </div>
        <div class="hero-feature">
          <span>Secure Patient Records</span>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="hero-visual">
        <div class="hero-visual-card">
          <div class="hero-visual-icon" aria-hidden="true">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>
          </div>
          <div class="hero-visual-title">Direct Doctor Scheduling</div>
          <p class="hero-visual-text">
            Consult with top healthcare specialists across multiple medical departments with zero waiting room hassle.
          </p>
          <div class="hero-visual-stats">
            <div class="hero-stat">
              <span class="hero-stat-value"><?= count($depts) ?></span>
              <span class="hero-stat-label">Specialties</span>
            </div>
            <div class="hero-stat">
              <span class="hero-stat-value"><?= count($docs) ?></span>
              <span class="hero-stat-label">Doctors</span>
            </div>
            <div class="hero-stat">
              <span class="hero-stat-value">100%</span>
              <span class="hero-stat-label">Online</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Quick Appointment Entry Point -->
<section class="mt-5 mb-5" aria-labelledby="quick-book-title">
  <div class="booking-search">
    <div class="row align-items-center mb-3">
      <div class="col-md-8">
        <h4 id="quick-book-title" class="fw-bold mb-1" style="color: var(--primary);">Quick Appointment Search</h4>
        <p class="text-muted small mb-0">Select a specialist and preferred date to check available consultation slots.</p>
      </div>
    </div>
    <form method="get" action="/book.php" class="row g-3 align-items-end">
      <div class="col-md-5">
        <label for="quick_doctor_id" class="form-label">Doctor & Specialty</label>
        <select id="quick_doctor_id" name="doctor_id" class="form-select" required>
          <option value="">Choose a specialist...</option>
          <?php foreach ($docs as $d): ?>
            <option value="<?= (int)$d['id'] ?>">
              <?= e($d['name']) ?> — <?= e($d['dept']) ?> (₹<?= e($d['fee']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label for="quick_date" class="form-label">Appointment Date</label>
        <input type="date" id="quick_date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required>
      </div>
      <div class="col-md-3">
        <button type="submit" class="btn btn-primary w-100">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
          Check Slots
        </button>
      </div>
    </form>
  </div>
</section>

<!-- Departments & Services Section -->
<section id="departments" class="mb-5" aria-labelledby="dept-heading">
  <div class="section-header">
    <div>
      <h3 id="dept-heading">Specialized Departments</h3>
      <p class="section-subtitle">Our hospital offers expert consultation across specialized clinical faculties.</p>
    </div>
  </div>

  <div class="row g-4">
    <?php 
    // SVG icons tailored for each department
    $deptIcons = [
      'General Medicine' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"></path></svg>',
      'Cardiology' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>',
      'Orthopedics' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="5" r="3"></circle><line x1="12" y1="8" x2="12" y2="15"></line><path d="M9 11l3 2 3-2"></path><path d="M8 21l4-6 4 6"></path></svg>',
      'Pediatrics' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M8 14s1.5 2 4 2 4-2 4-2"></path><line x1="9" y1="9" x2="9.01" y2="9"></line><line x1="15" y1="9" x2="15.01" y2="9"></line></svg>',
      'Dermatology' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>',
    ];
    $defaultIcon = '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>';
    ?>
    <?php foreach ($depts as $dept): ?>
      <div class="col-md-4 col-sm-6">
        <div class="service-card h-100">
          <div class="service-icon" aria-hidden="true">
            <?= $deptIcons[$dept['name']] ?? $defaultIcon ?>
          </div>
          <h5><?= e($dept['name']) ?></h5>
          <p class="text-muted small mb-3">
            Comprehensive care and specialized clinical consultations in <?= e(strtolower($dept['name'])) ?>.
          </p>
          <div class="fee-pill">
            <?= (int)$dept['doctor_count'] ?> Active Specialist<?= (int)$dept['doctor_count'] !== 1 ? 's' : '' ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- How It Works Section -->
<section id="how-it-works" class="mb-5 pt-3" aria-labelledby="how-heading">
  <div class="section-header">
    <div>
      <h3 id="how-heading">How Appointment Booking Works</h3>
      <p class="section-subtitle">A streamlined, 4-step process designed for fast, seamless scheduling.</p>
    </div>
  </div>

  <div class="steps-grid">
    <div class="step-card">
      <div class="step-number" aria-hidden="true">1</div>
      <h5>Create an Account</h5>
      <p>Register with your name, email, and phone number or log in to your patient account.</p>
    </div>
    
    <div class="step-card">
      <div class="step-number" aria-hidden="true">2</div>
      <h5>Select Specialist & Date</h5>
      <p>Browse active doctors across departments and select your desired consultation date.</p>
    </div>

    <div class="step-card">
      <div class="step-number" aria-hidden="true">3</div>
      <h5>Lock an Available Slot</h5>
      <p>Pick a free time slot. Our database locking system prevents any duplicate bookings.</p>
    </div>

    <div class="step-card">
      <div class="step-number" aria-hidden="true">4</div>
      <h5>Track & Get Notified</h5>
      <p>Receive email confirmation, view your status, and manage medical reports securely.</p>
    </div>
  </div>
</section>

<!-- Meet Our Doctors Section -->
<section id="doctors" class="mb-5" aria-labelledby="doctors-heading">
  <div class="section-header">
    <div>
      <h3 id="doctors-heading">Meet Our Medical Team</h3>
      <p class="section-subtitle">Experienced physicians committed to delivering personalized patient care.</p>
    </div>
    <div>
      <a class="btn btn-outline-primary btn-sm d-none d-md-inline-flex" href="/book.php">
        View Schedules
      </a>
    </div>
  </div>

  <div class="row g-4">
    <?php foreach ($docs as $d): ?>
      <div class="col-lg-4 col-md-6">
        <div class="doctor-card h-100">
          <div class="card-body d-flex flex-column justify-content-between">
            <div>
              <div class="doctor-avatar" aria-hidden="true">
                <?= strtoupper(substr(trim((string)$d['name']), 0, 1)) ?>
              </div>
              <h5><?= e($d['name']) ?></h5>
              <div class="doctor-meta">
                <span class="fw-semibold text-primary"><?= e($d['dept']) ?></span>
                <?php if (!empty($d['qualification'])): ?>
                  &middot; <span class="text-muted"><?= e($d['qualification']) ?></span>
                <?php endif; ?>
              </div>
            </div>
            <div class="d-flex align-items-center justify-content-between mt-4 pt-3 border-top">
              <span class="fee-pill">₹<?= number_format((float)$d['fee'], 2) ?> Fee</span>
              <a class="btn btn-sm btn-primary" href="/book.php?doctor_id=<?= (int)$d['id'] ?>">
                Book Slot
              </a>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- Trust & Convenience Callout -->
<section class="cta-section mb-4" aria-labelledby="trust-heading">
  <h3 id="trust-heading">Reliable Online Healthcare Scheduling</h3>
  <p>
    Manage your appointments effortlessly without phone queues. Schedule ahead, view your upcoming visits, and keep your medical records organized in one secure place.
  </p>
  <div class="d-flex justify-content-center gap-3 flex-wrap">
    <a class="btn btn-primary btn-lg" href="/book.php">
      Schedule Your Visit Today
    </a>
    <?php if (!current_user()): ?>
      <a class="btn btn-outline-dark btn-lg" href="/register.php">
        Create Patient Account
      </a>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
