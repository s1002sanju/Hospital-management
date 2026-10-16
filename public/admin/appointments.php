<?php require __DIR__ . '/../../includes/bootstrap.php'; require_admin(); $title = 'Appointments'; $pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') { csrf_check(); $id = (int)$_POST['id']; $new = $_POST['status'] ?? '';
    if (in_array($new, ['completed', 'cancelled'], true)) {
        $a = $pdo->prepare("SELECT slot_id FROM appointments WHERE id=? AND status='booked'"); $a->execute([$id]); $row = $a->fetch();
        if ($row) { $pdo->beginTransaction();
            $pdo->prepare("UPDATE appointments SET status=?, active_slot_id=" . ($new === 'cancelled' ? 'NULL' : 'active_slot_id') . " WHERE id=?")->execute([$new, $id]);
            if ($new === 'cancelled') $pdo->prepare("UPDATE slots SET is_booked=false WHERE id=?")->execute([$row['slot_id']]);
            $pdo->commit(); flash('success', "Appointment #$id marked $new."); } }
    redirect('/admin/appointments.php'); }
$f = $_GET['status'] ?? ''; $sql = "SELECT a.id, a.status, a.reason, u.name AS patient, u.phone, s.slot_date, s.start_time, dr.name AS doctor FROM appointments a JOIN users u ON u.id=a.user_id JOIN slots s ON s.id=a.slot_id JOIN doctors dr ON dr.id=s.doctor_id";
$args = []; if (in_array($f, ['booked', 'cancelled', 'completed'], true)) { $sql .= " WHERE a.status=?"; $args[] = $f; }
$st = $pdo->prepare($sql . " ORDER BY s.slot_date DESC, s.start_time DESC LIMIT 300"); $st->execute($args); $rows = $st->fetchAll();
require __DIR__ . '/../../includes/header.php'; ?>
<h3>All Appointments</h3><div class="mb-2"><a href="?" class="btn btn-sm btn-outline-dark">All</a> <a href="?status=booked" class="btn btn-sm btn-outline-success">Booked</a> <a href="?status=completed" class="btn btn-sm btn-outline-info">Completed</a> <a href="?status=cancelled" class="btn btn-sm btn-outline-secondary">Cancelled</a></div>
<table class="table table-striped bg-white"><tr><th>#</th><th>Patient</th><th>Doctor</th><th>Date</th><th>Time</th><th>Reason</th><th>Status</th><th></th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= (int)$r['id'] ?></td><td><?= e($r['patient']) ?><br><small><?= e($r['phone']) ?></small></td><td><?= e($r['doctor']) ?></td><td><?= e($r['slot_date']) ?></td><td><?= e(substr($r['start_time'], 0, 5)) ?></td><td><?= e($r['reason']) ?></td><td><?= e($r['status']) ?></td>
<td><?php if ($r['status'] === 'booked'): ?><form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button name="status" value="completed" class="btn btn-sm btn-success">Complete</button> <button name="status" value="cancelled" class="btn btn-sm btn-danger">Cancel</button></form><?php endif; ?></td></tr><?php endforeach; ?></table>
<?php require __DIR__ . '/../../includes/footer.php';
