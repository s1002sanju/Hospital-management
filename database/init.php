<?php
// Start-up tasks, run by docker/entrypoint.sh before Apache starts (Render's free plan has no shell).
// Every step is idempotent and NEVER fatal: if something fails it is logged and the site still starts.
//   1. apply database/supabase_schema.sql      (AUTO_MIGRATE=1, default) - creates tables / upgrades old ones
//   2. create or promote the first admin       (ADMIN_EMAIL + ADMIN_PASSWORD) - password is never reset afterwards
//   3. create the private Supabase Storage bucket for medical reports
require __DIR__ . '/../includes/bootstrap.php';

function init_log(string $m): void { fwrite(STDERR, "[init] $m\n"); }

if (DB_HOST === '' || DB_USER === '') { init_log('DB_HOST / DB_USER not set - skipping all start-up tasks.'); exit(0); }

try {
    $pdo = db();
    init_log('database connection OK (' . DB_HOST . ':' . DB_PORT . ')');
} catch (Throwable $e) {
    init_log('database connection FAILED: ' . $e->getMessage());
    exit(0);
}

// 1. schema
if (is_pgsql() && getenv('AUTO_MIGRATE') !== '0') {
    try {
        $pdo->exec(file_get_contents(__DIR__ . '/supabase_schema.sql'));
        init_log('schema is up to date');
    } catch (Throwable $e) { init_log('schema step FAILED: ' . $e->getMessage()); }
}

// 2. first admin
$adminEmail = strtolower(trim((string)getenv('ADMIN_EMAIL')));
$adminPass  = (string)getenv('ADMIN_PASSWORD');
if ($adminEmail !== '' && $adminPass !== '') {
    try {
        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL) || strlen($adminPass) < 8) {
            init_log('ADMIN_EMAIL invalid or ADMIN_PASSWORD shorter than 8 characters - admin not created.');
        } else {
            $st = $pdo->prepare('SELECT id, role FROM users WHERE email=?'); $st->execute([$adminEmail]); $row = $st->fetch();
            if (!$row) {
                $pdo->prepare("INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,'admin')")
                    ->execute([getenv('ADMIN_NAME') ?: 'Administrator', $adminEmail, password_hash($adminPass, PASSWORD_BCRYPT)]);
                init_log("admin $adminEmail created");
            } elseif ($row['role'] !== 'admin') {
                $pdo->prepare("UPDATE users SET role='admin' WHERE id=?")->execute([$row['id']]);
                init_log("existing user $adminEmail promoted to admin");
            } else {
                init_log("admin $adminEmail already exists");
            }
        }
    } catch (Throwable $e) { init_log('admin step FAILED: ' . $e->getMessage()); }
}

// 3. storage bucket
if (storage_bucket_is_supabase()) {
    try {
        $base = SUPABASE_URL . '/storage/v1/bucket';
        $get = http_json('GET', $base . '/' . rawurlencode(SUPABASE_STORAGE_BUCKET), supabase_storage_headers());
        if ($get['code'] === 200) {
            init_log('storage bucket "' . SUPABASE_STORAGE_BUCKET . '" exists');
        } else {
            $mk = http_json('POST', $base, supabase_storage_headers(['Content-Type: application/json']), json_encode([
                'id' => SUPABASE_STORAGE_BUCKET, 'name' => SUPABASE_STORAGE_BUCKET, 'public' => false,
                'file_size_limit' => 2097152, 'allowed_mime_types' => ['application/pdf', 'image/jpeg', 'image/png'],
            ]));
            init_log($mk['code'] >= 200 && $mk['code'] < 300
                ? 'storage bucket "' . SUPABASE_STORAGE_BUCKET . '" created (private)'
                : 'storage bucket create FAILED: HTTP ' . $mk['code'] . ' ' . $mk['body'] . ' ' . $mk['error']);
        }
    } catch (Throwable $e) { init_log('storage step FAILED: ' . $e->getMessage()); }
} else {
    init_log('Supabase Storage not configured (SUPABASE_URL / SUPABASE_SERVICE_ROLE_KEY) - reports will not work on Render.');
}
exit(0);
