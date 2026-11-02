<?php 
require __DIR__ . '/../includes/bootstrap.php'; 
$user = require_login(); 
$title = 'My Appointments'; 
$pdo = db();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_check(); 
    $id = (int)($_POST['id'] ?? 0);
    $own = $pdo->prepare("SELECT * FROM appointments WHERE id=? AND user_id=? AND status='booked'"); 
    $own->execute([$id, $user['id']]); 
    $a = $own->fetch();

    if (!$a) { 
        flash('danger', 'Appointment not found or cannot be modified.'); 
        redirect('/my_appointments.php'); 
    }

    if (($_POST['action'] ?? '') === 'cancel') {
        try {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE appointments SET status='cancelled', active_slot_id=NULL WHERE id=?")->execute([$id]);
            $pdo->prepare("UPDATE slots SET is_booked=false WHERE id=?")->execute([$a['slot_id']]);
            $pdo->commit();
        } catch (Throwable $ex) { 
            if ($pdo->inTransaction()) $pdo->rollBack(); 
            error_log((string)$ex); 
            flash('danger', 'Could not cancel appointment. Please try again.'); 
            redirect('/my_appointments.php'); 
        }
        send_email($user['email'], 'Appointment cancelled - ' . APP_NAME, "Hello {$user['name']},\n\nYour appointment #$id has been cancelled as requested.\n\n" . APP_NAME);
        flash('success', 'Appointment #' . $id . ' has been cancelled.');
    } elseif (($_POST['action'] ?? '') === 'upload') {
        $f = $_FILES['report'] ?? null;
        if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) { 
            flash('danger', 'Please choose a file to upload first.'); 
        } elseif ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE) { 
            flash('danger', 'File is too large. Maximum allowed file size is 2 MB.'); 
        } elseif ($f['error'] !== UPLOAD_ERR_OK) { 
            flash('danger', 'Upload failed. Please try again.'); 
        } else {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
            $ext = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'][$mime] ?? null;
            if (!$ext || $f['size'] > 2 * 1024 * 1024) { 
                flash('danger', 'Invalid file type or size. Only PDF, JPG, and PNG files up to 2 MB are permitted.'); 
            } else {
                $key = "reports/{$user['id']}/appt{$id}_" . bin2hex(random_bytes(6)) . ".$ext";
                if (store_report($f['tmp_name'], $key, $mime)) {
                    $pdo->prepare("UPDATE appointments SET report_key=? WHERE id=?")->execute([$key, $id]);
                    flash('success', 'Medical report uploaded securely.');
                } else { 
                    flash('danger', 'Report storage service is unavailable right now. Please try again later.'); 
                }
            }
        }
    }
    redirect('/my_appointments.php');
}

$st = $pdo->prepare("SELECT a.*, s.slot_date, s.start_time, dr.name AS doctor, dp.name AS dept 
                     FROM appointments a 
                     JOIN slots s ON s.id=a.slot_id 
                     JOIN doctors dr ON dr.id=s.doctor_id 
                     JOIN departments dp ON dp.id=dr.department_id 
                     WHERE a.user_id=? 
                     ORDER BY s.slot_date DESC, s.start_time DESC");
$st->execute([$user['id']]); 
$rows = $st->fetchAll();

// Accurate stats calculated from user's real appointment history
$totalCount = count($rows);
$bookedCount = count(array_filter($rows, fn($r) => $r['status'] === 'booked'));
$completedCount = count(array_filter($rows, fn($r) => $r['status'] === 'completed'));
$cancelledCount = count(array_filter($rows, fn($r) => $r['status'] === 'cancelled'));

require __DIR__ . '/../includes/header.php'; 
?>

<!-- Patient Dashboard Header -->
<div class="page-header">
  <div>
    <h3 class="page-title">My Appointments</h3>
    <p class="welcome-text">
      Welcome back, <strong><?= e($user['name']) ?></strong>. Manage your scheduled visits and medical reports.
    </p>
  </div>
  <div>
    <a href="/book.php" class="btn btn-primary">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
      Book New Appointment
    </a>
  </div>
</div>

<!-- Accurate Stat Cards -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-card-icon stat-card-icon-primary" aria-hidden="true">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
    </div>
    <span class="stat-value"><?= $totalCount ?></span>
    <span class="stat-label">Total Visits</span>
  </div>

  <div class="stat-card">
    <div class="stat-card-icon stat-card-icon-success" aria-hidden="true">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
    </div>
    <span class="stat-value"><?= $bookedCount ?></span>
    <span class="stat-label">Upcoming / Booked</span>
  </div>

  <div class="stat-card">
    <div class="stat-card-icon stat-card-icon-info" aria-hidden="true">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
    </div>
    <span class="stat-value"><?= $completedCount ?></span>
    <span class="stat-label">Completed</span>
  </div>

  <div class="stat-card">
    <div class="stat-card-icon stat-card-icon-secondary" aria-hidden="true">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
    </div>
    <span class="stat-value"><?= $cancelledCount ?></span>
    <span class="stat-label">Cancelled</span>
  </div>
</div>

<?php if (!$rows): ?>
  <!-- Empty State -->
  <div class="card">
    <div class="card-body">
      <div class="empty-state">
        <div class="empty-state-icon" aria-hidden="true">
          <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
        </div>
        <h5>No Appointments Found</h5>
        <p>You have not booked any consultations yet. Schedule your first visit with one of our medical specialists.</p>
        <a href="/book.php" class="btn btn-primary">
          Book an Appointment Now
        </a>
      </div>
    </div>
  </div>
<?php else: ?>

  <!-- Desktop Table View -->
  <div class="table-wrapper d-none d-md-block mb-4">
    <table class="table" aria-label="Appointment History">
      <thead>
        <tr>
          <th scope="col">ID</th>
          <th scope="col">Doctor & Department</th>
          <th scope="col">Date & Time</th>
          <th scope="col">Reason</th>
          <th scope="col">Status</th>
          <th scope="col">Medical Report</th>
          <th scope="col" class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="fw-semibold text-muted">#<?= (int)$r['id'] ?></td>
            <td>
              <div class="fw-bold text-primary"><?= e($r['doctor']) ?></div>
              <div class="small text-muted"><?= e($r['dept']) ?></div>
            </td>
            <td>
              <div class="fw-medium"><?= date('D, M j, Y', strtotime($r['slot_date'])) ?></div>
              <div class="small text-muted"><?= e(substr($r['start_time'], 0, 5)) ?></div>
            </td>
            <td>
              <?php if (!empty($r['reason'])): ?>
                <span class="text-muted small"><?= e($r['reason']) ?></span>
              <?php else: ?>
                <span class="text-muted small italic">General visit</span>
              <?php endif; ?>
            </td>
            <td>
              <?php
                $statusClass = match($r['status']) {
                  'booked' => 'badge-booked',
                  'completed' => 'badge-completed',
                  'cancelled' => 'badge-cancelled',
                  default => 'bg-secondary'
                };
              ?>
              <span class="badge badge-status <?= $statusClass ?>">
                <?= e(ucfirst($r['status'])) ?>
              </span>
            </td>
            <td>
              <?php if (!empty($r['report_key'])): ?>
                <a href="/report.php?id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                  View Report
                </a>
              <?php elseif ($r['status'] === 'booked'): ?>
                <form method="post" enctype="multipart/form-data" class="d-flex align-items-center gap-1">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <input type="hidden" name="action" value="upload">
                  <input type="file" name="report" accept=".pdf,.jpg,.jpeg,.png" class="form-control form-control-sm" style="max-width: 140px;" required title="Upload PDF/JPG/PNG up to 2MB">
                  <button type="submit" class="btn btn-sm btn-light" title="Upload Report">
                    Upload
                  </button>
                </form>
              <?php else: ?>
                <span class="text-muted small">—</span>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <?php if ($r['status'] === 'booked'): ?>
                <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel appointment #<?= (int)$r['id'] ?> with <?= e($r['doctor']) ?>?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button type="submit" name="action" value="cancel" class="btn btn-sm btn-danger" data-no-loader="true">
                    Cancel Visit
                  </button>
                </form>
              <?php else: ?>
                <span class="text-muted small">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Mobile Card View (Optimized for Small Screens) -->
  <div class="d-md-none">
    <?php foreach ($rows as $r): ?>
      <div class="appointment-card">
        <div class="appointment-card-header">
          <div>
            <span class="text-muted small">#<?= (int)$r['id'] ?></span>
            <h5 class="mb-0 fw-bold text-primary"><?= e($r['doctor']) ?></h5>
            <div class="small text-muted"><?= e($r['dept']) ?></div>
          </div>
          <?php
            $statusClass = match($r['status']) {
              'booked' => 'badge-booked',
              'completed' => 'badge-completed',
              'cancelled' => 'badge-cancelled',
              default => 'bg-secondary'
            };
          ?>
          <span class="badge badge-status <?= $statusClass ?>">
            <?= e(ucfirst($r['status'])) ?>
          </span>
        </div>

        <div class="appointment-card-details">
          <div>
            <div class="appointment-detail-label">Date</div>
            <div class="appointment-detail-value"><?= date('D, M j, Y', strtotime($r['slot_date'])) ?></div>
          </div>
          <div>
            <div class="appointment-detail-label">Time</div>
            <div class="appointment-detail-value"><?= e(substr($r['start_time'], 0, 5)) ?></div>
          </div>
          <?php if (!empty($r['reason'])): ?>
            <div class="col-12" style="grid-column: 1 / -1;">
              <div class="appointment-detail-label">Reason</div>
              <div class="appointment-detail-value small text-muted"><?= e($r['reason']) ?></div>
            </div>
          <?php endif; ?>
        </div>

        <div class="appointment-card-actions">
          <?php if (!empty($r['report_key'])): ?>
            <a href="/report.php?id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">
              View Report
            </a>
          <?php elseif ($r['status'] === 'booked'): ?>
            <form method="post" enctype="multipart/form-data" class="d-flex align-items-center gap-1 w-100 mb-2">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <input type="hidden" name="action" value="upload">
              <input type="file" name="report" accept=".pdf,.jpg,.jpeg,.png" class="form-control form-control-sm" required>
              <button type="submit" class="btn btn-sm btn-light">Upload</button>
            </form>
          <?php endif; ?>

          <?php if ($r['status'] === 'booked'): ?>
            <form method="post" class="w-100" onsubmit="return confirm('Cancel this appointment?');">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button type="submit" name="action" value="cancel" class="btn btn-sm btn-danger w-100" data-no-loader="true">
                Cancel Visit
              </button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
