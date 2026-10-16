<?php require __DIR__ . '/../../includes/bootstrap.php'; require_admin(); $title = 'Doctors'; $pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') { csrf_check();
    $a = $_POST['action'] ?? '';
    if ($a === 'add_dept' && trim($_POST['name'] ?? '') !== '') {
        $sql = is_pgsql() ? "INSERT INTO departments (name) VALUES (?) ON CONFLICT (name) DO NOTHING" : "INSERT IGNORE INTO departments (name) VALUES (?)";
        $pdo->prepare($sql)->execute([trim($_POST['name'])]);
    }
    if ($a === 'add_doc' && trim($_POST['name'] ?? '') !== '') { $pdo->prepare("INSERT INTO doctors (name, department_id, qualification, fee) VALUES (?,?,?,?)")->execute([trim($_POST['name']), (int)$_POST['department_id'], trim($_POST['qualification'] ?? ''), (float)($_POST['fee'] ?? 0)]); }
    if ($a === 'toggle') { $pdo->prepare("UPDATE doctors SET is_active = NOT is_active WHERE id=?")->execute([(int)$_POST['id']]); }
    redirect('/admin/doctors.php'); }
$depts = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();
$docs = $pdo->query("SELECT d.*, dp.name AS dept FROM doctors d JOIN departments dp ON dp.id=d.department_id ORDER BY d.name")->fetchAll();
require __DIR__ . '/../../includes/header.php'; ?>
<h3>Doctors & Departments</h3><div class="row"><div class="col-md-4"><div class="card mb-3"><div class="card-body"><h6>Add department</h6>
<form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="add_dept"><input class="form-control mb-2" name="name" required><button class="btn btn-sm btn-primary">Add</button></form></div></div>
<div class="card"><div class="card-body"><h6>Add doctor</h6><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="add_doc">
<input class="form-control mb-2" name="name" placeholder="Name" required>
<select class="form-select mb-2" name="department_id"><?php foreach ($depts as $d): ?><option value="<?= (int)$d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?></select>
<input class="form-control mb-2" name="qualification" placeholder="Qualification"><input class="form-control mb-2" type="number" step="0.01" name="fee" placeholder="Fee">
<button class="btn btn-sm btn-primary">Add</button></form></div></div></div>
<div class="col-md-8"><table class="table bg-white"><tr><th>Name</th><th>Dept</th><th>Fee</th><th>Active</th></tr>
<?php foreach ($docs as $d): ?><tr><td><?= e($d['name']) ?></td><td><?= e($d['dept']) ?></td><td><?= e($d['fee']) ?></td>
<td><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$d['id'] ?>"><button class="btn btn-sm btn-<?= $d['is_active'] ? 'success' : 'secondary' ?>"><?= $d['is_active'] ? 'Active' : 'Inactive' ?></button></form></td></tr><?php endforeach; ?></table></div></div>
<?php require __DIR__ . '/../../includes/footer.php';
