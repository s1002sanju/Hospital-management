<?php 
require __DIR__ . '/../../includes/bootstrap.php'; 
require_admin(); 
$title = 'Generate Consultation Slots'; 
$pdo = db();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') { 
    csrf_check();
    $doc = (int)$_POST['doctor_id']; 
    $from = new DateTime($_POST['from']); 
    $to = new DateTime($_POST['to']);
    $dur = max(10, min(120, (int)$_POST['duration'])); 
    $made = 0;

    $sql = is_pgsql()
        ? "INSERT INTO slots (doctor_id, slot_date, start_time, end_time) VALUES (?,?,?,?) ON CONFLICT (doctor_id, slot_date, start_time) DO NOTHING"
        : "INSERT IGNORE INTO slots (doctor_id, slot_date, start_time, end_time) VALUES (?,?,?,?)";
    $ins = $pdo->prepare($sql);

    for ($d = clone $from; $d <= $to && $made < 5000; $d->modify('+1 day')) {
        if ($d->format('N') == 7) continue; // skip Sundays
        $t = new DateTime($d->format('Y-m-d') . ' ' . $_POST['start']); 
        $end = new DateTime($d->format('Y-m-d') . ' ' . $_POST['end']);
        while ($t < $end) { 
            $e2 = (clone $t)->modify("+$dur minutes"); 
            if ($e2 > $end) break;
            $ins->execute([$doc, $d->format('Y-m-d'), $t->format('H:i:s'), $e2->format('H:i:s')]); 
            $made += $ins->rowCount(); 
            $t = $e2; 
        } 
    }
    flash('success', "$made slots created."); 
    redirect('/admin/slots.php'); 
}

$docs = $pdo->query("SELECT d.id, d.name, dp.name AS dept FROM doctors d JOIN departments dp ON dp.id=d.department_id WHERE d.is_active=true ORDER BY dp.name, d.name")->fetchAll();

// Slot summary statistics
$upcomingSlots = (int)$pdo->query("SELECT COUNT(*) FROM slots WHERE slot_date >= " . sql_today())->fetchColumn();
$bookedSlots = (int)$pdo->query("SELECT COUNT(*) FROM slots WHERE slot_date >= " . sql_today() . " AND is_booked=true")->fetchColumn();

require __DIR__ . '/../../includes/header.php'; 
?>

<div class="page-header">
  <div>
    <h3 class="page-title">Generate Consultation Slots</h3>
    <p class="page-subtitle mb-0">Create recurring appointment availability for active doctors in batch.</p>
  </div>
  <div>
    <a href="/admin/appointments.php" class="btn btn-outline-primary btn-sm">
      View Appointments
    </a>
  </div>
</div>

<div class="row g-4">
  <!-- Slot Generation Form -->
  <div class="col-lg-8">
    <div class="card mb-4">
      <div class="card-body">
        <h5 class="fw-bold mb-3">Batch Slot Creator</h5>
        
        <form method="post" aria-label="Generate Slots Form">
          <?= csrf_field() ?>

          <div class="form-group mb-3">
            <label for="slot-doctor" class="form-label">Select Physician</label>
            <select id="slot-doctor" name="doctor_id" class="form-select" required>
              <option value="">Select an active doctor...</option>
              <?php foreach ($docs as $d): ?>
                <option value="<?= (int)$d['id'] ?>">
                  <?= e($d['name']) ?> (<?= e($d['dept']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label for="slot-from" class="form-label">From Date</label>
              <input 
                type="date" 
                id="slot-from" 
                name="from" 
                class="form-control" 
                value="<?= date('Y-m-d') ?>" 
                min="<?= date('Y-m-d') ?>" 
                required
              >
            </div>
            <div class="col-md-6">
              <label for="slot-to" class="form-label">To Date</label>
              <input 
                type="date" 
                id="slot-to" 
                name="to" 
                class="form-control" 
                value="<?= date('Y-m-d', strtotime('+7 days')) ?>" 
                min="<?= date('Y-m-d') ?>" 
                required
              >
            </div>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-md-4">
              <label for="slot-start" class="form-label">Shift Start Time</label>
              <input 
                type="time" 
                id="slot-start" 
                name="start" 
                class="form-control" 
                value="09:00" 
                required
              >
            </div>
            <div class="col-md-4">
              <label for="slot-end" class="form-label">Shift End Time</label>
              <input 
                type="time" 
                id="slot-end" 
                name="end" 
                class="form-control" 
                value="13:00" 
                required
              >
            </div>
            <div class="col-md-4">
              <label for="slot-duration" class="form-label">Duration (Minutes)</label>
              <input 
                type="number" 
                id="slot-duration" 
                name="duration" 
                class="form-control" 
                value="30" 
                min="10" 
                max="120" 
                required
              >
              <div class="form-helper">Allowed range: 10 to 120 min.</div>
            </div>
          </div>

          <button type="submit" class="btn btn-primary btn-lg">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            Generate Available Slots
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Guidance & Statistics Sidebar -->
  <div class="col-lg-4">
    <div class="card mb-4">
      <div class="card-body">
        <h6 class="fw-bold mb-3">Live Slot Metrics</h6>
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
          <span class="text-muted small">Upcoming Slots (Future):</span>
          <span class="fw-bold text-primary"><?= $upcomingSlots ?></span>
        </div>
        <div class="d-flex justify-content-between align-items-center py-2">
          <span class="text-muted small">Currently Booked:</span>
          <span class="fw-bold text-success"><?= $bookedSlots ?></span>
        </div>
      </div>
    </div>

    <div class="card" style="background: var(--bg); border: 1px solid var(--border);">
      <div class="card-body">
        <h6 class="fw-bold mb-2">How Slot Generation Works</h6>
        <ul class="small text-muted ps-3 mb-0" style="line-height: 1.6;">
          <li class="mb-2"><strong>Schedule:</strong> Creates slots Mon–Sat between shift start and end time.</li>
          <li class="mb-2"><strong>Sundays Skipped:</strong> Sunday dates are automatically bypassed.</li>
          <li class="mb-2"><strong>Idempotent:</strong> Duplicate slots for the same doctor and time are ignored and never overwritten.</li>
          <li><strong>Protection:</strong> Safety limit caps generation at 5,000 slots per batch.</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
