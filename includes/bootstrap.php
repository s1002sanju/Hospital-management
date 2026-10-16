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
    header("Content-Security-Policy: default-src 'self'; style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; script-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'");
    if ($isHttps) header('Strict-Transport-Security: max-age=31536000');
}

set_exception_handler(function (Throwable $e) {
    error_log((string)$e);
    http_response_code(500);
    echo 'Something went wrong. Please try again later.';
});

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $driver = defined('DB_DRIVER') ? DB_DRIVER : 'mysql';
        if ($driver === 'pgsql') {
            $host = DB_HOST;
            $port = DB_PORT;
            // Force IPv4 by resolving the hostname to avoid IPv6 timeout on Render free tier
            // If host resolves only to IPv6, connect via IP directly
            $dsn = 'pgsql:host=' . $host . ';port=' . $port . ';dbname=' . DB_NAME;
            if (defined('DB_SSLMODE') && DB_SSLMODE !== '') {
                $dsn .= ';sslmode=' . DB_SSLMODE;
            }
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 15,
            ]);
        } else {
            $dsn = 'mysql:host=' . DB_HOST;
            if (defined('DB_PORT') && DB_PORT !== '') {
                $dsn .= ';port=' . DB_PORT;
            }
            $dsn .= ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }
    }
    return $pdo;
}

function is_pgsql(): bool {
    static $isPg = null;
    if ($isPg === null) {
        $isPg = (db()->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql');
    }
    return $isPg;
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
