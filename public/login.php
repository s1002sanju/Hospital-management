<?php require __DIR__ . '/../includes/bootstrap.php'; $title = 'Login'; $err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $fails = $_SESSION['fails'] ?? ['n' => 0, 't' => time()];
    if ($fails['n'] >= 5 && time() - $fails['t'] < 300) { $err = 'Too many attempts. Try again in 5 minutes.'; }
    else {
        $st = db()->prepare("SELECT * FROM users WHERE email=?"); $st->execute([strtolower(trim($_POST['email'] ?? ''))]);
        $u = $st->fetch();
        if ($u && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
            session_regenerate_id(true); unset($_SESSION['fails']);
            $_SESSION['user'] = ['id' => (int)$u['id'], 'name' => $u['name'], 'email' => $u['email'], 'role' => $u['role']];
            redirect($u['role'] === 'admin' ? '/admin/index.php' : '/book.php');
        }
        $_SESSION['fails'] = ['n' => ($fails['n'] >= 5 ? 1 : $fails['n'] + 1), 't' => time()];
        $err = 'Invalid email or password.';
    }
}
require __DIR__ . '/../includes/header.php'; ?>
<div class="row justify-content-center"><div class="col-md-4"><div class="card"><div class="card-body">
<h4>Login</h4><?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>
<form method="post"><?= csrf_field() ?>
<input class="form-control mb-2" type="email" name="email" placeholder="Email" required>
<input class="form-control mb-3" type="password" name="password" placeholder="Password" required>
<button class="btn btn-primary w-100">Login</button></form></div></div></div></div>
<?php require __DIR__ . '/../includes/footer.php';
