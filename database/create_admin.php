<?php
// Usage: php database/create_admin.php "Admin Name" admin@example.com "StrongPassword123"
require __DIR__ . '/../includes/bootstrap.php';
[$_, $name, $email, $pass] = $argv + [null, null, null, null];
if (!$name || !$email || !$pass) { exit("Usage: php create_admin.php NAME EMAIL PASSWORD\n"); }
$st = db()->prepare("INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,'admin')");
$st->execute([$name, $email, password_hash($pass, PASSWORD_BCRYPT)]);
echo "Admin created.\n";
