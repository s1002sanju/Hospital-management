<?php
// All settings come from environment variables (set in Elastic Beanstalk -> Configuration -> Environment properties).
// Database configuration: supports MySQL (local/RDS) and PostgreSQL (Supabase).
// Defaults below can be overridden by environment variables (e.g. Render, Beanstalk, .env) or edited directly here.
if ($dbUrl = getenv('DATABASE_URL')) {
    $parsedUrl = parse_url($dbUrl);
    define('DB_DRIVER', in_array($parsedUrl['scheme'] ?? '', ['postgres', 'postgresql']) ? 'pgsql' : 'mysql');
    define('DB_HOST', $parsedUrl['host'] ?? '127.0.0.1');
    define('DB_PORT', (string)($parsedUrl['port'] ?? (DB_DRIVER === 'pgsql' ? '5432' : '3306')));
    define('DB_NAME', ltrim($parsedUrl['path'] ?? 'postgres', '/'));
    define('DB_USER', isset($parsedUrl['user']) ? urldecode($parsedUrl['user']) : '');
    define('DB_PASS', isset($parsedUrl['pass']) ? urldecode($parsedUrl['pass']) : '');
} else {
    // Supabase Session Pooler (IPv4, works on Render free tier)
    // Direct host db.xxx.supabase.co is IPv6-only and unreachable from Render
    $envHost = getenv('DB_HOST') ?: 'aws-0-ap-south-1.pooler.supabase.com';
    define('DB_DRIVER', getenv('DB_DRIVER') ?: 'pgsql');
    define('DB_HOST', $envHost);
    define('DB_PORT', getenv('DB_PORT') ?: '6543');
    define('DB_NAME', getenv('DB_NAME') ?: 'postgres');
    define('DB_USER', getenv('DB_USER') ?: 'postgres.lvadlqjgfbjrixnzdpkm');
    define('DB_PASS', getenv('DB_PASS') ?: 'Sanju@supabase10');
}
define('DB_SSLMODE', getenv('DB_SSLMODE') ?: 'require');
define('AWS_REGION', getenv('AWS_REGION') ?: 'ap-south-1');
define('SES_SENDER', getenv('SES_SENDER') ?: '');          // verified SES email; empty = log instead of send
define('SNS_TOPIC_ARN', getenv('SNS_TOPIC_ARN') ?: '');     // admin notifications; empty = skip
define('S3_BUCKET', getenv('S3_BUCKET') ?: '');             // legacy AWS bucket for medical reports; empty = local storage/
define('SUPABASE_URL', getenv('SUPABASE_URL') ?: '');
define('SUPABASE_SERVICE_ROLE_KEY', getenv('SUPABASE_SERVICE_ROLE_KEY') ?: getenv('SUPABASE_SERVICE_KEY') ?: '');
define('SUPABASE_STORAGE_BUCKET', getenv('SUPABASE_STORAGE_BUCKET') ?: getenv('SUPABASE_BUCKET') ?: 'medical-reports');
define('REMINDER_KEY', getenv('REMINDER_KEY') ?: 'change-me-locally');
define('FORCE_HTTPS', getenv('FORCE_HTTPS') === '1');
define('APP_NAME', 'CityCare Hospital');
