<?php require __DIR__ . '/../includes/bootstrap.php'; $user = require_login(); $title = 'My Appointments'; $pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $id = (int)($_POST['id'] ?? 0);
    $own = $pdo->prepare("SELECT * FROM appointments WHERE id=? AND user_id=? AND status='booked'"); $own->execute([$id, $user['id']]); $a = $own->fetch();
    if (!$a) { flash('danger', 'Appointment not found.'); redirect('/my_appointments.php'); }
    if (($_POST['action'] ?? '') === 'cancel') {
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE appointments SET status='cancelled', active_slot_id=NULL WHERE id=?")->execute([$id]);
        $pdo->prepare("UPDATE slots SET is_booked=false WHERE id=?")->execute([$a['slot_id']]);
        $pdo->commit();
        send_email($user['email'], 'Appointment cancelled', "Your appointment #$id was cancelled.");
        flash('success', 'Appointment cancelled.');
    } elseif (($_POST['action'] ?? '') === 'upload' && isset($_FILES['report']) && $_FILES['report']['error'] === UPLOAD_ERR_OK) {
        $f = $_FILES['report']; $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        $ext = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'][$mime] ?? null;
        if (!$ext || $f['size'] > 2 * 1024 * 1024) { flash('danger', 'Only PDF/JPG/PNG up to 2 MB allowed.'); }
        else {
            $key = "reports/{$user['id']}/appt{$id}_" . bin2hex(random_bytes(6)) . ".$ext";
            store_report($f['tmp_name'], $key);
            $pdo->prepare("UPDATE appointments SET report_key=? WHERE id=?")->execute([$key, $id]);
            flash('success', 'Report uploaded securely.');
        }
    }
    redirect('/my_appointments.php');
}
$st = $pdo->prepare("SELECT a.*, s.slot_date, s.start_time, dr.name AS doctor FROM appointments a JOIN slots s ON s.id=a.slot_id JOIN doctors dr ON dr.id=s.doctor_id WHERE a.user_id=? ORDER BY s.slot_date DESC, s.start_time DESC");
$st->execute([$user['id']]); $rows = $st->fetchAll();
require __DIR__ . '/../includes/header.php'; ?>
<h3>My Appointments</h3>
<table class="table table-striped bg-white"><thead><tr><th>#</th><th>Doctor</th><th>Date</th><th>Time</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= (int)$r['id'] ?></td><td><?= e($r['doctor']) ?></td><td><?= e($r['slot_date']) ?></td><td><?= e(substr($r['start_time'], 0, 5)) ?></td>
<td><span class="badge bg-<?= $r['status'] === 'booked' ? 'success' : ($r['status'] === 'cancelled' ? 'secondary' : 'info') ?>"><?= e($r['status']) ?></span></td>
<td><?php if ($r['status'] === 'booked'): ?>
 <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button name="action" value="cancel" class="btn btn-sm btn-danger" onclick="return confirm('Cancel this appointment?')">Cancel</button></form>
 <form method="post" enctype="multipart/form-data" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="action" value="upload">
 <input type="file" name="report" accept=".pdf,.jpg,.jpeg,.png" class="form-control form-control-sm d-inline w-auto"><button class="btn btn-sm btn-secondary">Upload report</button></form>
<?php endif; ?><?php if ($r['report_key']): ?> <a class="btn btn-sm btn-link" href="/report.php?id=<?= (int)$r['id'] ?>">View report</a><?php endif; ?></td></tr><?php endforeach; ?>
</tbody></table>
<?php require __DIR__ . '/../includes/footer.php';
