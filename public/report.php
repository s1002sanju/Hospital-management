<?php 
require __DIR__ . '/../includes/bootstrap.php'; 
$user = require_login();

$st = db()->prepare("SELECT report_key, user_id FROM appointments WHERE id=?"); 
$st->execute([(int)($_GET['id'] ?? 0)]); 
$a = $st->fetch();

// Authorization: only the owner or an admin may view the report.
if (!$a || !$a['report_key'] || ((int)$a['user_id'] !== (int)$user['id'] && $user['role'] !== 'admin')) { 
    flash('danger', 'The requested medical report was not found or you do not have permission to view it.');
    redirect($user['role'] === 'admin' ? '/admin/appointments.php' : '/my_appointments.php');
}

if (storage_bucket_is_supabase() || (S3_BUCKET !== '' && class_exists('Aws\\S3\\S3Client'))) {
    $url = report_url($a['report_key']); // Supabase / S3 signed URL (valid 5 min)
    if ($url) {
        redirect($url);
    }
    flash('danger', 'The report could not be retrieved from secure storage right now. Please try again shortly.');
    redirect($user['role'] === 'admin' ? '/admin/appointments.php' : '/my_appointments.php');
}

$path = __DIR__ . '/../storage/' . $a['report_key']; // local development fallback
if (!is_file($path)) { 
    flash('danger', 'The report file is unavailable on local storage.');
    redirect($user['role'] === 'admin' ? '/admin/appointments.php' : '/my_appointments.php');
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . basename($a['report_key']) . '"');
readfile($path);
exit;
