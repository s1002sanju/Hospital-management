<?php require __DIR__ . '/../../includes/bootstrap.php'; require_admin(); $title = 'Slots'; $pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') { csrf_check();
    $doc = (int)$_POST['doctor_id']; $from = new DateTime($_POST['from']); $to = new DateTime($_POST['to']);
    $dur = max(10, min(120, (int)$_POST['duration'])); $made = 0;
    $sql = is_pgsql()
        ? "INSERT INTO slots (doctor_id, slot_date, start_time, end_time) VALUES (?,?,?,?) ON CONFLICT (doctor_id, slot_date, start_time) DO NOTHING"
        : "INSERT IGNORE INTO slots (doctor_id, slot_date, start_time, end_time) VALUES (?,?,?,?)";
    $ins = $pdo->prepare($sql);
    for ($d = clone $from; $d <= $to && $made < 5000; $d->modify('+1 day')) {
        if ($d->format('N') == 7) continue;                             // skip Sundays
        $t = new DateTime($d->format('Y-m-d') . ' ' . $_POST['start']); $end = new DateTime($d->format('Y-m-d') . ' ' . $_POST['end']);
        while ($t < $end) { $e2 = (clone $t)->modify("+$dur minutes"); if ($e2 > $end) break;
            $ins->execute([$doc, $d->format('Y-m-d'), $t->format('H:i:s'), $e2->format('H:i:s')]); $made += $ins->rowCount(); $t = $e2; } }
    flash('success', "$made slots created."); redirect('/admin/slots.php'); }
$docs = $pdo->query("SELECT id, name FROM doctors WHERE is_active=true ORDER BY name")->fetchAll();
require __DIR__ . '/../../includes/header.php'; ?>
<h3>Generate Slots</h3><form method="post" class="row g-2 bg-white p-3 rounded shadow-sm"><?= csrf_field() ?>
<div class="col-md-4"><select name="doctor_id" class="form-select"><?php foreach ($docs as $d): ?><option value="<?= (int)$d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-2"><input type="date" name="from" class="form-control" required></div><div class="col-md-2"><input type="date" name="to" class="form-control" required></div>
<div class="col-md-1"><input type="time" name="start" class="form-control" value="09:00"></div><div class="col-md-1"><input type="time" name="end" class="form-control" value="13:00"></div>
<div class="col-md-1"><input type="number" name="duration" class="form-control" value="30" title="minutes"></div><div class="col-md-1"><button class="btn btn-primary w-100">Create</button></div></form>
<p class="text-muted mt-2">Creates slots Mon–Sat between start and end time, every N minutes. Existing slots are never duplicated.</p>
<?php require __DIR__ . '/../../includes/footer.php';
