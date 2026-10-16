<?php
// All settings come from ENVIRONMENT VARIABLES (Render -> Service -> Environment).
// Never put passwords or keys in this file: it is committed to git.

function cfg_env(string $key, string $default = ''): string {
    $v = getenv($key);
    return ($v === false || $v === '') ? $default : $v;
}

// ---- Database: PostgreSQL (Supabase) by default, MySQL still supported for local dev ----
// Option A: one DATABASE_URL  (postgresql://USER:URL-ENCODED-PASSWORD@HOST:PORT/postgres)
// Option B: separate DB_HOST / DB_PORT / DB_NAME / DB_USER / DB_PASS  (no URL-encoding needed)
$dbUrl = cfg_env('DATABASE_URL');
if ($dbUrl !== '' && ($p = parse_url($dbUrl)) !== false && !empty($p['host'])) {
    $scheme = $p['scheme'] ?? '';
    define('DB_DRIVER', in_array($scheme, ['postgres', 'postgresql', 'pgsql'], true) ? 'pgsql' : 'mysql');
    define('DB_HOST', $p['host']);
    define('DB_PORT', (string)($p['port'] ?? (DB_DRIVER === 'pgsql' ? 5432 : 3306)));
    define('DB_NAME', ltrim($p['path'] ?? '', '/') ?: 'postgres');
    define('DB_USER', isset($p['user']) ? urldecode($p['user']) : '');
    define('DB_PASS', isset($p['pass']) ? urldecode($p['pass']) : '');
} else {
    define('DB_DRIVER', cfg_env('DB_DRIVER', 'pgsql'));
    define('DB_HOST', cfg_env('DB_HOST'));
    define('DB_PORT', cfg_env('DB_PORT', DB_DRIVER === 'pgsql' ? '5432' : '3306'));
    define('DB_NAME', cfg_env('DB_NAME', DB_DRIVER === 'pgsql' ? 'postgres' : 'hospital_db'));
    define('DB_USER', cfg_env('DB_USER'));
    define('DB_PASS', cfg_env('DB_PASS'));
}
define('DB_SSLMODE', cfg_env('DB_SSLMODE', 'require'));   // Supabase needs 'require'; use 'disable' for local Postgres

// ---- App ----
define('APP_NAME', 'CityCare Hospital');
define('APP_ENV', cfg_env('APP_ENV', 'production'));
$tz = cfg_env('APP_TIMEZONE', 'Asia/Kolkata');
if (!in_array($tz, timezone_identifiers_list(), true)) $tz = 'Asia/Kolkata';   // also guards the SQL that embeds it
define('APP_TIMEZONE', $tz);
date_default_timezone_set(APP_TIMEZONE);
define('FORCE_HTTPS', cfg_env('FORCE_HTTPS') === '1');
define('REMINDER_KEY', cfg_env('REMINDER_KEY'));          // empty = reminder API disabled (fail closed)

// ---- Supabase Storage (medical reports) ----
define('SUPABASE_URL', rtrim(cfg_env('SUPABASE_URL'), '/'));   // https://<project-ref>.supabase.co
define('SUPABASE_SERVICE_ROLE_KEY', cfg_env('SUPABASE_SERVICE_ROLE_KEY', cfg_env('SUPABASE_SERVICE_KEY')));
define('SUPABASE_STORAGE_BUCKET', cfg_env('SUPABASE_STORAGE_BUCKET', cfg_env('SUPABASE_BUCKET', 'medical-reports')));

// ---- Email (Render blocks SMTP on the free plan, so use an HTTPS API such as Resend) ----
define('RESEND_API_KEY', cfg_env('RESEND_API_KEY'));
define('MAIL_FROM', cfg_env('MAIL_FROM'));                // e.g. "CityCare <noreply@yourdomain.com>"

// ---- Optional legacy AWS integrations (only used if the AWS SDK is installed) ----
define('AWS_REGION', cfg_env('AWS_REGION', 'ap-south-1'));
define('SES_SENDER', cfg_env('SES_SENDER'));
define('SNS_TOPIC_ARN', cfg_env('SNS_TOPIC_ARN'));
define('S3_BUCKET', cfg_env('S3_BUCKET'));