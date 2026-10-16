<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
if (is_file(__DIR__ . '/../vendor/autoload.php')) { require_once __DIR__ . '/../vendor/autoload.php'; }
require_once __DIR__ . '/notify.php';

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
$uri = $_SERVER['REQUEST_URI'] ?? '/';
if (FORCE_HTTPS && !$isHttps && PHP_SAPI !== 'cli' && strpos($uri, '/health.php') !== 0) {
    header('Location: https://' . ($_SERVER['HTTP_HOST'] ?? '') . $uri, true, 301); exit;
}
if (PHP_SAPI !== 'cli') {
    session_set_cookie_params(['httponly' => true, 'secure' => $isHttps, 'samesite' => 'Lax']);
    session_start();
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header("Content-Security-Policy: default-src 'self'; style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; script-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; img-src 'self' data:; base-uri 'self'; frame-ancestors 'none'");
    if ($isHttps) header('Strict-Transport-Security: max-age=31536000');
}

set_exception_handler(function (Throwable $e) {
    error_log((string)$e);                       // full detail goes to the Render log, never to the visitor
    if (!headers_sent()) http_response_code($e instanceof PDOException || $e instanceof RuntimeException ? 503 : 500);
    echo PHP_SAPI === 'cli' ? 'Error: ' . $e->getMessage() . PHP_EOL : 'Something went wrong. Please try again later.';
});

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;
    if (DB_HOST === '' || DB_USER === '') {
        throw new RuntimeException('Database is not configured: set DB_HOST, DB_USER and DB_PASS (or DATABASE_URL) in the environment.');
    }
    if (DB_DRIVER === 'pgsql') {
        $dsn = 'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME;
        if (DB_SSLMODE !== '') $dsn .= ';sslmode=' . DB_SSLMODE;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Emulated prepares are REQUIRED for the Supabase pooler in transaction mode (port 6543),
            // which does not support server-side prepared statements. Works fine on port 5432 too.
            PDO::ATTR_EMULATE_PREPARES => true,
            PDO::ATTR_TIMEOUT => 10,
        ]);
    } else {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

function is_pgsql(): bool { return DB_DRIVER === 'pgsql'; }

// Time helpers. The hospital works in APP_TIMEZONE (IST) but Supabase runs in UTC, so compare in local time.
function sql_now_local(): string { return is_pgsql() ? "(NOW() AT TIME ZONE '" . APP_TIMEZONE . "')" : 'NOW()'; }
function sql_today(): string     { return is_pgsql() ? '(' . sql_now_local() . ')::date' : 'CURDATE()'; }
function sql_tomorrow(): string  { return is_pgsql() ? '((' . sql_now_local() . ')::date + 1)' : 'DATE_ADD(CURDATE(), INTERVAL 1 DAY)'; }
function sql_slot_in_future(string $alias = ''): string {
    $p = $alias === '' ? '' : $alias . '.';
    return is_pgsql() ? "({$p}slot_date + {$p}start_time) > " . sql_now_local() : "TIMESTAMP({$p}slot_date,{$p}start_time) > NOW()";
}
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(403); exit('Invalid CSRF token'); }
}

function current_user(): ?array { return $_SESSION['user'] ?? null; }
function redirect(string $to): never { header('Location: ' . $to); exit; }
function flash(string $type, string $msg): void { $_SESSION['flash'] = [$type, $msg]; }
function require_login(): array { $u = current_user(); if (!$u) redirect('/login.php'); return $u; }
function require_admin(): array { $u = require_login(); if ($u['role'] !== 'admin') { http_response_code(403); exit('Forbidden'); } return $u; }
