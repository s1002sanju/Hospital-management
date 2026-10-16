<?php require __DIR__ . '/../includes/bootstrap.php'; $title = 'Home';
$docs = db()->query("SELECT d.id, d.name, d.qualification, d.fee, dp.name AS dept FROM doctors d JOIN departments dp ON dp.id=d.department_id WHERE d.is_active=true ORDER BY dp.name, d.name")->fetchAll();
require __DIR__ . '/../includes/header.php'; ?>
<div class="p-4 mb-4 bg-white rounded shadow-sm"><h1>Book your doctor appointment online</h1>
<p class="lead">Check live availability, book instantly, get email confirmation and reminders.</p>
<a class="btn btn-primary btn-lg" href="/book.php">Book Appointment</a></div>
<h4>Our Doctors</h4><div class="row">
<?php foreach ($docs as $d): ?><div class="col-md-4 mb-3"><div class="card h-100"><div class="card-body">
<h5><?= e($d['name']) ?></h5><div class="text-muted"><?= e($d['dept']) ?> &middot; <?= e($d['qualification']) ?></div>
<div class="mt-2">Fee: &#8377;<?= e($d['fee']) ?></div></div></div></div><?php endforeach; ?></div>
<?php require __DIR__ . '/../includes/footer.php';
