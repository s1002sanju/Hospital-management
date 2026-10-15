<?php require __DIR__ . '/../includes/bootstrap.php'; $user = require_login(); $title = 'Book Appointment';
$pdo = db();

// ---- Handle booking (POST) -------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $slotId = (int)($_POST['slot_id'] ?? 0); $reason = substr(trim($_POST['reason'] ?? ''), 0, 255);
    try {
        $pdo->beginTransaction();
        // Atomic claim: only ONE concurrent request can flip is_booked 0 -> 1 (prevents double booking).
        $claim = $pdo->prepare("UPDATE slots SET is_booked=1 WHERE id=? AND is_booked=0 AND TIMESTAMP(slot_date,start_time) > NOW()");
        $claim->execute([$slotId]);
        if ($claim->rowCount() !== 1) { $pdo->rollBack(); flash('danger', 'Sorry, that slot was just taken or has passed. Pick another.'); redirect($_SERVER['REQUEST_URI']); }
        $pdo->prepare("INSERT INTO appointments (user_id, slot_id, active_slot_id, reason) VALUES (?,?,?,?)")
            ->execute([$user['id'], $slotId, $slotId, $reason]);
        $apptId = (int)$pdo->lastInsertId();
        $pdo->commit();
    } catch (Throwable $ex) { if ($pdo->inTransaction()) $pdo->rollBack(); error_log((string)$ex); flash('danger', 'Booking failed. Please try again.'); redirect('/book.php'); }

    $d = $pdo->prepare("SELECT s.slot_date, s.start_time, dr.name AS doctor FROM slots s JOIN doctors dr ON dr.id=s.doctor_id WHERE s.id=?");
    $d->execute([$slotId]); $info = $d->fetch();
    $msg = "Appointment #$apptId confirmed with {$info['doctor']} on {$info['slot_date']} at " . substr($info['start_time'], 0, 5) . '.';
    send_email($user['email'], 'Appointment confirmed', "Hello {$user['name']},\n$msg");   // Amazon SES
    publish_event('New booking', $msg . " Patient: {$user['name']}");                      // Amazon SNS
    flash('success', $msg); redirect('/my_appointments.php');
}

// ---- Show doctors / dates / free slots (GET) ------------------------------------
$doctors = $pdo->query("SELECT d.id, d.name, dp.name AS dept FROM doctors d JOIN departments dp ON dp.id=d.department_id WHERE d.is_active=1 ORDER BY dp.name, d.name")->fetchAll();
$doctorId = (int)($_GET['doctor_id'] ?? 0);
$date = $_GET['date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = date('Y-m-d');
$slots = [];
if ($doctorId) {
    $st = $pdo->prepare("SELECT id, start_time, end_time FROM slots WHERE doctor_id=? AND slot_date=? AND is_booked=0 AND TIMESTAMP(slot_date,start_time) > NOW() ORDER BY start_time");
    $st->execute([$doctorId, $date]); $slots = $st->fetchAll();
}
require __DIR__ . '/../includes/header.php'; ?>
<h3>Book an Appointment</h3>
<form method="get" class="row g-2 mb-4 bg-white p-3 rounded shadow-sm">
  <div class="col-md-5"><select name="doctor_id" class="form-select" required><option value="">Select doctor</option>
    <?php foreach ($doctors as $d): ?><option value="<?= (int)$d['id'] ?>" <?= $doctorId === (int)$d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?> (<?= e($d['dept']) ?>)</option><?php endforeach; ?></select></div>
  <div class="col-md-4"><input type="date" name="date" class="form-control" value="<?= e($date) ?>" min="<?= date('Y-m-d') ?>"></div>
  <div class="col-md-3"><button class="btn btn-primary w-100">Check availability</button></div>
</form>
<?php if ($doctorId): ?>
  <h5>Available slots on <?= e($date) ?></h5>
  <?php if (!$slots): ?><p class="text-muted">No free slots for this doctor/date.</p><?php endif; ?>
  <form method="post"><?= csrf_field() ?>
  <?php if ($slots): ?><input class="form-control mb-3" name="reason" maxlength="255" placeholder="Reason for visit (optional)"><?php endif; ?>
  <?php foreach ($slots as $s): ?><button name="slot_id" value="<?= (int)$s['id'] ?>" class="btn btn-outline-success m-1"><?= e(substr($s['start_time'], 0, 5)) ?></button><?php endforeach; ?>
  </form>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php';
