<?php require __DIR__ . '/../includes/bootstrap.php'; $user = require_login();
$st = db()->prepare("SELECT report_key, user_id FROM appointments WHERE id=?"); $st->execute([(int)($_GET['id'] ?? 0)]); $a = $st->fetch();
// Authorization: only the owner or an admin may view the report.
if (!$a || !$a['report_key'] || ((int)$a['user_id'] !== (int)$user['id'] && $user['role'] !== 'admin')) { http_response_code(404); exit('Not found'); }
if (storage_bucket_is_supabase() || (S3_BUCKET !== '' && class_exists('Aws\\S3\\S3Client'))) {
    $url = report_url($a['report_key']);           // Supabase / S3 signed URL (valid 5 min)
    if ($url) redirect($url);
    http_response_code(502); exit('The report could not be retrieved right now. Please try again shortly.');
}
$path = __DIR__ . '/../storage/' . $a['report_key'];  // local fallback
if (!is_file($path)) { http_response_code(404); exit('Not found'); }
header('Content-Type: ' . (new finfo(FILEINFO_MIME_TYPE))->file($path)); readfile($path);
