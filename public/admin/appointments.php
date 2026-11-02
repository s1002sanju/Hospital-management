<?php 
require __DIR__ . '/../../includes/bootstrap.php'; 
require_admin(); 
$title = 'Manage Appointments'; 
$pdo = db();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') { 
    csrf_check(); 
    $id = (int)($_POST['id'] ?? 0); 
    $new = $_POST['status'] ?? '';

    if (in_array($new, ['completed', 'cancelled'], true)) {
        $a = $pdo->prepare("SELECT slot_id FROM appointments WHERE id=? AND status='booked'"); 
        $a->execute([$id]); 
        $row = $a->fetch();

        if ($row) { 
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE appointments SET status=?, active_slot_id=" . ($new === 'cancelled' ? 'NULL' : 'active_slot_id') . " WHERE id=?")->execute([$new, $id]);
            if ($new === 'cancelled') {
                $pdo->prepare("UPDATE slots SET is_booked=false WHERE id=?")->execute([$row['slot_id']]);
            }
            $pdo->commit(); 
            flash('success', "Appointment #$id successfully marked as $new."); 
        } 
    }
    redirect('/admin/appointments.php' . (!empty($_GET['status']) ? '?status=' . urlencode($_GET['status']) : '')); 
}

$f = $_GET['status'] ?? ''; 
$sql = "SELECT a.id, a.status, a.reason, a.report_key, u.name AS patient, u.phone, u.email, s.slot_date, s.start_time, dr.name AS doctor 
        FROM appointments a 
        JOIN users u ON u.id=a.user_id 
        JOIN slots s ON s.id=a.slot_id 
        JOIN doctors dr ON dr.id=s.doctor_id";

$args = []; 
if (in_array($f, ['booked', 'cancelled', 'completed'], true)) { 
    $sql .= " WHERE a.status=?"; 
    $args[] = $f; 
}

$st = $pdo->prepare($sql . " ORDER BY s.slot_date DESC, s.start_time DESC LIMIT 300"); 
$st->execute($args); 
$rows = $st->fetchAll();

// Get counts for filter pills
$counts = $pdo->query("SELECT 
    COUNT(*) as total,
    COUNT(CASE WHEN status='booked' THEN 1 END) as booked,
    COUNT(CASE WHEN status='completed' THEN 1 END) as completed,
    COUNT(CASE WHEN status='cancelled' THEN 1 END) as cancelled
    FROM appointments")->fetch();

require __DIR__ . '/../../includes/header.php'; 
?>

<!-- Admin Appointments Header -->
<div class="page-header">
  <div>
    <h3 class="page-title">Appointment Management</h3>
    <p class="page-subtitle mb-0">Review, update, and manage patient bookings across all hospital departments.</p>
  </div>
  <div>
    <a href="/admin/slots.php" class="btn btn-primary btn-sm">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
      Generate Slots
    </a>
  </div>
</div>

<!-- Filter Pills -->
<div class="filter-pills">
  <a href="/admin/appointments.php" class="filter-pill <?= $f === '' ? 'active' : '' ?>">
    All Appointments (<?= (int)$counts['total'] ?>)
  </a>
  <a href="/admin/appointments.php?status=booked" class="filter-pill <?= $f === 'booked' ? 'active' : '' ?>">
    Booked (<?= (int)$counts['booked'] ?>)
  </a>
  <a href="/admin/appointments.php?status=completed" class="filter-pill <?= $f === 'completed' ? 'active' : '' ?>">
    Completed (<?= (int)$counts['completed'] ?>)
  </a>
  <a href="/admin/appointments.php?status=cancelled" class="filter-pill <?= $f === 'cancelled' ? 'active' : '' ?>">
    Cancelled (<?= (int)$counts['cancelled'] ?>)
  </a>
</div>

<!-- Appointments Table Card -->
<div class="card mb-4">
  <div class="card-body p-0">
    <?php if (!$rows): ?>
      <div class="empty-state py-5">
        <div class="empty-state-icon" aria-hidden="true">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
        </div>
        <h5>No Appointments Found</h5>
        <p class="text-muted">
          <?= $f ? 'There are no ' . e($f) . ' appointments matching your filter.' : 'No appointment records in the database.' ?>
        </p>
        <?php if ($f): ?>
          <a href="/admin/appointments.php" class="btn btn-outline-primary btn-sm">Clear Filter</a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="table-wrapper border-0 shadow-none">
        <table class="table mb-0" aria-label="Appointments List">
          <thead>
            <tr>
              <th scope="col">ID</th>
              <th scope="col">Patient Details</th>
              <th scope="col">Doctor</th>
              <th scope="col">Consultation Time</th>
              <th scope="col">Reason</th>
              <th scope="col">Status</th>
              <th scope="col" class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td class="fw-semibold text-muted">#<?= (int)$r['id'] ?></td>
                <td>
                  <div class="fw-bold text-primary"><?= e($r['patient']) ?></div>
                  <?php if (!empty($r['phone'])): ?>
                    <div class="small text-muted">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="vertical-align: -1px;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                      <?= e($r['phone']) ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="fw-medium"><?= e($r['doctor']) ?></div>
                </td>
                <td>
                  <div class="fw-medium"><?= date('D, M j, Y', strtotime($r['slot_date'])) ?></div>
                  <div class="small text-muted"><?= e(substr($r['start_time'], 0, 5)) ?></div>
                </td>
                <td>
                  <?php if (!empty($r['reason'])): ?>
                    <span class="small text-muted"><?= e($r['reason']) ?></span>
                  <?php else: ?>
                    <span class="small text-muted italic">None stated</span>
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
                <td class="text-end">
                  <?php if ($r['status'] === 'booked'): ?>
                    <form method="post" class="d-inline-flex gap-1" onsubmit="return confirm('Confirm updating appointment #<?= (int)$r['id'] ?>?');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                      <button type="submit" name="status" value="completed" class="btn btn-sm btn-success" title="Mark as Completed" data-no-loader="true">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        Complete
                      </button>
                      <button type="submit" name="status" value="cancelled" class="btn btn-sm btn-danger" title="Cancel Appointment" data-no-loader="true">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        Cancel
                      </button>
                    </form>
                  <?php else: ?>
                    <?php if (!empty($r['report_key'])): ?>
                      <a href="/report.php?id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">
                        Report
                      </a>
                    <?php else: ?>
                      <span class="text-muted small">—</span>
                    <?php endif; ?>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
