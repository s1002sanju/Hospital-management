<?php 
require __DIR__ . '/../includes/bootstrap.php'; 
$user = require_login(); 
$title = 'Book Appointment';
$pdo = db();

// ---- Handle booking (POST) -------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_check();
    $slotId = (int)($_POST['slot_id'] ?? 0); 
    $reason = substr(trim($_POST['reason'] ?? ''), 0, 255);
    
    try {
        $pdo->beginTransaction();
        // Atomic claim: only ONE concurrent request can flip is_booked 0 -> 1 (prevents double booking).
        $claim = $pdo->prepare("UPDATE slots SET is_booked=true WHERE id=? AND is_booked=false AND " . sql_slot_in_future());
        $claim->execute([$slotId]);
        
        if ($claim->rowCount() !== 1) { 
            $pdo->rollBack(); 
            flash('danger', 'Sorry, that slot was just taken or has passed. Pick another.'); 
            redirect($_SERVER['REQUEST_URI']); 
        }

        $insertSql = is_pgsql()
            ? "INSERT INTO appointments (user_id, slot_id, active_slot_id, reason) VALUES (?,?,?,?) RETURNING id"
            : "INSERT INTO appointments (user_id, slot_id, active_slot_id, reason) VALUES (?,?,?,?)";
        $ins = $pdo->prepare($insertSql);
        $ins->execute([$user['id'], $slotId, $slotId, $reason]);
        $apptId = is_pgsql() ? (int)$ins->fetchColumn() : (int)$pdo->lastInsertId();
        $pdo->commit();
    } catch (Throwable $ex) { 
        if ($pdo->inTransaction()) $pdo->rollBack(); 
        error_log((string)$ex); 
        flash('danger', 'Booking failed due to a server error. Please try again.'); 
        redirect('/book.php'); 
    }

    $d = $pdo->prepare("SELECT s.slot_date, s.start_time, dr.name AS doctor FROM slots s JOIN doctors dr ON dr.id=s.doctor_id WHERE s.id=?");
    $d->execute([$slotId]); 
    $info = $d->fetch();
    
    $formattedTime = substr($info['start_time'] ?? '', 0, 5);
    $msg = "Appointment #$apptId confirmed with {$info['doctor']} on {$info['slot_date']} at {$formattedTime}.";
    
    send_email($user['email'], 'Appointment confirmed - ' . APP_NAME, "Hello {$user['name']},\n\n$msg\n\nThank you for choosing " . APP_NAME . ".");
    publish_event('New booking', $msg . " Patient: {$user['name']}");
    
    flash('success', $msg); 
    redirect('/my_appointments.php');
}

// ---- Show doctors / dates / free slots (GET) ------------------------------------
$doctors = $pdo->query("SELECT d.id, d.name, d.qualification, d.fee, dp.name AS dept FROM doctors d JOIN departments dp ON dp.id=d.department_id WHERE d.is_active=true ORDER BY dp.name, d.name")->fetchAll();
$doctorId = (int)($_GET['doctor_id'] ?? 0);
$date = $_GET['date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = date('Y-m-d');

$slots = [];
$selectedDoc = null;
if ($doctorId) {
    foreach ($doctors as $doc) {
        if ((int)$doc['id'] === $doctorId) {
            $selectedDoc = $doc;
            break;
        }
    }
    $st = $pdo->prepare("SELECT id, start_time, end_time FROM slots WHERE doctor_id=? AND slot_date=? AND is_booked=false AND " . sql_slot_in_future() . " ORDER BY start_time");
    $st->execute([$doctorId, $date]); 
    $slots = $st->fetchAll();
}

require __DIR__ . '/../includes/header.php'; 
?>

<div class="page-header">
  <div>
    <h3 class="page-title">Book an Appointment</h3>
    <p class="page-subtitle mb-0">Select your preferred doctor, choose a date, and reserve an available consultation slot.</p>
  </div>
  <div>
    <a href="/my_appointments.php" class="btn btn-outline-primary">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
      My Appointments
    </a>
  </div>
</div>

<!-- Step 1: Doctor & Date Selection Form -->
<div class="card mb-4">
  <div class="card-body">
    <div class="d-flex align-items-center gap-2 mb-3">
      <span class="badge bg-primary">Step 1</span>
      <h5 class="mb-0 fw-bold">Select Specialist and Consultation Date</h5>
    </div>
    
    <form method="get" action="/book.php" class="row g-3 align-items-end" aria-label="Find Available Slots">
      <div class="col-md-5">
        <label for="doctor_select" class="form-label">Specialist & Department</label>
        <select id="doctor_select" name="doctor_id" class="form-select" required>
          <option value="">Choose a doctor...</option>
          <?php foreach ($doctors as $d): ?>
            <option value="<?= (int)$d['id'] ?>" <?= $doctorId === (int)$d['id'] ? 'selected' : '' ?>>
              <?= e($d['name']) ?> (<?= e($d['dept']) ?>) — ₹<?= number_format((float)$d['fee'], 2) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-4">
        <label for="date_select" class="form-label">Preferred Date</label>
        <input 
          type="date" 
          id="date_select" 
          name="date" 
          class="form-control" 
          value="<?= e($date) ?>" 
          min="<?= date('Y-m-d') ?>"
          required
        >
      </div>

      <div class="col-md-3">
        <button type="submit" class="btn btn-primary w-100">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
          Check Availability
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Step 2: Available Slots & Booking Confirmation -->
<?php if ($doctorId && $selectedDoc): ?>
  <div class="card mb-4">
    <div class="card-body">
      <!-- Doctor Summary Header -->
      <div class="d-flex flex-wrap align-items-center justify-content-between p-3 rounded-3 mb-4" style="background: var(--bg); border: 1px solid var(--border);">
        <div class="d-flex align-items-center gap-3">
          <div class="doctor-avatar mb-0" style="width: 48px; height: 48px; font-size: 1.1rem;" aria-hidden="true">
            <?= strtoupper(substr(trim((string)$selectedDoc['name']), 0, 1)) ?>
          </div>
          <div>
            <h5 class="mb-0 fw-bold"><?= e($selectedDoc['name']) ?></h5>
            <div class="small text-muted">
              <?= e($selectedDoc['dept']) ?> 
              <?php if (!empty($selectedDoc['qualification'])): ?>
                &middot; <?= e($selectedDoc['qualification']) ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div class="d-flex align-items-center gap-3 mt-2 mt-sm-0">
          <div class="text-end">
            <div class="small text-muted">Consultation Fee</div>
            <div class="fw-bold text-primary">₹<?= number_format((float)$selectedDoc['fee'], 2) ?></div>
          </div>
          <span class="badge bg-info">
            <?= date('D, M j, Y', strtotime($date)) ?>
          </span>
        </div>
      </div>

      <div class="d-flex align-items-center gap-2 mb-3">
        <span class="badge bg-primary">Step 2</span>
        <h5 class="mb-0 fw-bold">Select a Time Slot & Confirm Visit</h5>
      </div>

      <?php if (!$slots): ?>
        <div class="empty-state py-5">
          <div class="empty-state-icon" aria-hidden="true">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
          </div>
          <h5>No Free Slots Available</h5>
          <p class="text-muted">
            All consultation slots for <?= e($selectedDoc['name']) ?> on <?= date('F j, Y', strtotime($date)) ?> are fully booked or have ended. Please select another date above.
          </p>
        </div>
      <?php else: ?>
        <form method="post" aria-label="Confirm Appointment Booking">
          <?= csrf_field() ?>

          <div class="mb-4">
            <label for="visit-reason" class="form-label">
              Reason for Visit <span class="text-muted fw-normal">(Optional)</span>
            </label>
            <input 
              type="text" 
              id="visit-reason" 
              name="reason" 
              class="form-control" 
              maxlength="255" 
              placeholder="Brief description of symptoms, routine checkup, or follow-up..."
            >
            <div class="form-helper">This helps your doctor prepare for the consultation (maximum 255 characters).</div>
          </div>

          <label class="form-label mb-2">Available Time Slots (Click to Book)</label>
          <div class="d-flex flex-wrap gap-2 mb-2">
            <?php foreach ($slots as $s): ?>
              <?php 
                $startTime = substr($s['start_time'], 0, 5); 
                $endTime = substr($s['end_time'], 0, 5); 
              ?>
              <button 
                type="submit" 
                name="slot_id" 
                value="<?= (int)$s['id'] ?>" 
                class="slot-btn"
                title="Book consultation at <?= e($startTime) ?>"
              >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="margin-right: 4px; vertical-align: -1px;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                <?= e($startTime) ?>
              </button>
            <?php endforeach; ?>
          </div>
          <div class="form-helper mt-2">
            Clicking on an available time slot immediately locks and confirms your appointment.
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>
<?php else: ?>
  <!-- Guide state before selecting doctor -->
  <div class="card">
    <div class="card-body text-center py-5">
      <div class="empty-state-icon" aria-hidden="true">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
          <circle cx="9" cy="7" r="4"></circle>
          <polyline points="16 11 18 13 22 9"></polyline>
        </svg>
      </div>
      <h5 class="fw-bold">Ready to Schedule Your Consultation?</h5>
      <p class="text-muted" style="max-width: 460px; margin: 0 auto 1.5rem;">
        Choose a doctor and date from the form above to view live consultation time slots and reserve your visit.
      </p>
      <div class="d-inline-flex gap-2">
        <span class="badge bg-success">Real-Time Booking</span>
        <span class="badge bg-info">No Waiting Lines</span>
        <span class="badge bg-secondary">Free Cancellation</span>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
