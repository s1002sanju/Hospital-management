<?php
// Temporary diagnostic page - protected by REMINDER_KEY
// Remove this file after debugging is done
require_once __DIR__ . '/../config/config.php';

if (($_GET['key'] ?? '') !== REMINDER_KEY) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

header('Content-Type: text/plain');
echo "=== CityCare Hospital Diagnostics ===\n\n";

// PHP version
echo "PHP: " . PHP_VERSION . "\n";
echo "OS: " . PHP_OS . "\n\n";

// PDO drivers
echo "PDO Drivers: " . implode(', ', PDO::getAvailableDrivers()) . "\n\n";

// Config (masked)
echo "DB_DRIVER: " . (defined('DB_DRIVER') ? DB_DRIVER : '(not set)') . "\n";
echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : '(not set)') . "\n";
echo "DB_PORT: " . (defined('DB_PORT') ? DB_PORT : '(not set)') . "\n";
echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : '(not set)') . "\n";
echo "DB_USER: " . (defined('DB_USER') ? DB_USER : '(not set)') . "\n";
echo "DB_PASS: " . (defined('DB_PASS') ? '***' . substr(DB_PASS, -3) : '(not set)') . "\n";
echo "DB_SSLMODE: " . (defined('DB_SSLMODE') ? DB_SSLMODE : '(not set)') . "\n\n";

// DNS / TCP test
if (defined('DB_HOST')) {
    echo "DNS lookup for " . DB_HOST . ":\n";
    $records = dns_get_record(DB_HOST, DNS_A | DNS_AAAA);
    if ($records) {
        foreach ($records as $r) {
            echo "  " . $r['type'] . ": " . ($r['ip'] ?? $r['ipv6'] ?? '?') . "\n";
        }
    } else {
        echo "  No DNS records found\n";
    }
    
    echo "\nTCP connect to " . DB_HOST . ":" . (defined('DB_PORT') ? DB_PORT : 5432) . ":\n";
    $fp = @fsockopen(DB_HOST, defined('DB_PORT') ? (int)DB_PORT : 5432, $errno, $errstr, 5);
    if ($fp) {
        echo "  SUCCESS\n";
        fclose($fp);
    } else {
        echo "  FAILED: [$errno] $errstr\n";
    }
}

// PDO connection test
echo "\nPDO Connection test:\n";
try {
    require_once __DIR__ . '/../includes/bootstrap.php';
    $pdo = db();
    echo "  SUCCESS! Driver: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n";
    echo "  Doctors: " . $pdo->query('SELECT count(*) FROM doctors')->fetchColumn() . "\n";
} catch (Throwable $e) {
    echo "  FAILED: " . get_class($e) . "\n";
    echo "  Code: " . $e->getCode() . "\n";
    echo "  Message: " . $e->getMessage() . "\n";
}

