<?php require __DIR__ . '/../../includes/bootstrap.php'; require_admin(); $title = 'Dashboard'; $pdo = db();
$tot = $pdo->query("SELECT COUNT(*) total, COUNT(CASE WHEN status='booked' THEN 1 END) booked, COUNT(CASE WHEN status='cancelled' THEN 1 END) cancelled, COUNT(CASE WHEN status='completed' THEN 1 END) completed FROM appointments")->fetch();
$today = (int)$pdo->query("SELECT COUNT(*) FROM appointments a JOIN slots s ON s.id=a.slot_id WHERE s.slot_date=" . sql_today() . " AND a.status='booked'")->fetchColumn();
$patients = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='patient'")->fetchColumn();
$byDept = $pdo->query("SELECT dp.name, COUNT(a.id) c FROM departments dp LEFT JOIN doctors d ON d.department_id=dp.id LEFT JOIN slots s ON s.doctor_id=d.id LEFT JOIN appointments a ON a.slot_id=s.id AND a.status<>'cancelled' GROUP BY dp.id ORDER BY dp.name")->fetchAll();
require __DIR__ . '/../../includes/header.php'; ?>
<h3>Admin Dashboard</h3><div class="row mb-4 text-center">
<?php foreach ([['Patients', $patients, 'primary'], ["Today's bookings", $today, 'success'], ['Total booked', (int)$tot['booked'], 'info'], ['Cancelled', (int)$tot['cancelled'], 'secondary']] as [$l, $v, $c]): ?>
<div class="col-md-3 mb-2"><div class="card border-<?= $c ?>"><div class="card-body"><div class="display-6"><?= $v ?></div><?= e($l) ?></div></div></div><?php endforeach; ?></div>
<div class="row"><div class="col-md-6"><canvas id="deptChart"></canvas></div>
<div class="col-md-6"><canvas id="statusChart"></canvas></div></div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('deptChart'),{type:'bar',data:{labels:<?= json_encode(array_column($byDept, 'name')) ?>,datasets:[{label:'Appointments by department',data:<?= json_encode(array_map('intval', array_column($byDept, 'c'))) ?>}]}});
new Chart(document.getElementById('statusChart'),{type:'doughnut',data:{labels:['Booked','Cancelled','Completed'],datasets:[{data:[<?= (int)$tot['booked'] ?>,<?= (int)$tot['cancelled'] ?>,<?= (int)$tot['completed'] ?>]}]}});
</script>
<?php require __DIR__ . '/../../includes/footer.php';
