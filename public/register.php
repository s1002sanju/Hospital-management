<?php require __DIR__ . '/../includes/bootstrap.php'; $title = 'Register'; $err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['name'] ?? ''); $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? ''); $pass = $_POST['password'] ?? '';
    if ($name === '' || strlen($name) > 100) $err = 'Enter a valid name.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'Enter a valid email.';
    elseif (!preg_match('/^[0-9+\- ]{7,20}$/', $phone)) $err = 'Enter a valid phone number.';
    elseif (strlen($pass) < 8 || !preg_match('/[A-Za-z]/', $pass) || !preg_match('/[0-9]/', $pass)) $err = 'Password needs 8+ characters with letters and digits.';
    else {
        try {
            db()->prepare("INSERT INTO users (name,email,phone,password_hash) VALUES (?,?,?,?)")
                ->execute([$name, $email, $phone, password_hash($pass, PASSWORD_BCRYPT)]);
            flash('success', 'Registered! Please log in.'); redirect('/login.php');
        } catch (PDOException $ex) {
            $code = (string)$ex->getCode();
            $err = in_array($code, ['23000', '23505'], true) || str_starts_with($code, '23') ? 'Email already registered.' : 'Registration failed.';
        }
    }
}
require __DIR__ . '/../includes/header.php'; ?>
<div class="row justify-content-center"><div class="col-md-5"><div class="card"><div class="card-body">
<h4>Patient Registration</h4><?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>
<form method="post"><?= csrf_field() ?>
<input class="form-control mb-2" name="name" placeholder="Full name" required>
<input class="form-control mb-2" type="email" name="email" placeholder="Email" required>
<input class="form-control mb-2" name="phone" placeholder="Phone" required>
<input class="form-control mb-3" type="password" name="password" placeholder="Password (8+ chars, letters & digits)" required>
<button class="btn btn-primary w-100">Register</button></form></div></div></div></div>
<?php require __DIR__ . '/../includes/footer.php';
