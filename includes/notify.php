<?php
// Notifications (email / admin events) and medical-report storage.
//   Email   : Resend HTTPS API  ->  Amazon SES (if SDK installed)  ->  written to the log
//   Reports : Supabase Storage  ->  Amazon S3 (if SDK installed)   ->  local storage/ folder (dev only)

function http_json(string $method, string $url, array $headers, ?string $body = null, int $timeout = 10): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => $timeout,
    ]);
    if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    $resp = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    return ['code' => $code, 'body' => is_string($resp) ? $resp : '', 'error' => $err];
}

/** Returns true only if the message was really handed to a mail provider. */
function send_email(string $to, string $subject, string $body): bool {
    if (RESEND_API_KEY !== '' && MAIL_FROM !== '') {
        $r = http_json('POST', 'https://api.resend.com/emails',
            ['Authorization: Bearer ' . RESEND_API_KEY, 'Content-Type: application/json'],
            json_encode(['from' => MAIL_FROM, 'to' => [$to], 'subject' => $subject, 'text' => $body]));
        if ($r['code'] >= 200 && $r['code'] < 300) return true;
        error_log('Resend email failed: HTTP ' . $r['code'] . ' ' . $r['error'] . ' ' . $r['body']);
        return false;
    }
    if (SES_SENDER !== '' && class_exists('Aws\\Ses\\SesClient')) {
        try {
            $c = new Aws\Ses\SesClient(['version' => 'latest', 'region' => AWS_REGION]);
            $c->sendEmail([
                'Source' => SES_SENDER,
                'Destination' => ['ToAddresses' => [$to]],
                'Message' => ['Subject' => ['Data' => $subject], 'Body' => ['Text' => ['Data' => $body]]],
            ]);
            return true;
        } catch (Throwable $e) { error_log('SES error: ' . $e->getMessage()); return false; }
    }
    error_log("[EMAIL not sent - no provider configured] to=$to subject=$subject");
    return false;
}

function publish_event(string $subject, string $message): void {
    if (SNS_TOPIC_ARN !== '' && class_exists('Aws\\Sns\\SnsClient')) {
        try {
            $c = new Aws\Sns\SnsClient(['version' => 'latest', 'region' => AWS_REGION]);
            $c->publish(['TopicArn' => SNS_TOPIC_ARN, 'Subject' => $subject, 'Message' => $message]);
        } catch (Throwable $e) { error_log('SNS error: ' . $e->getMessage()); }
    } else { error_log("[EVENT] $subject :: $message"); }
}

// ---------------------------------------------------------------- Supabase Storage
function storage_bucket_is_supabase(): bool {
    return SUPABASE_URL !== '' && SUPABASE_SERVICE_ROLE_KEY !== '' && SUPABASE_STORAGE_BUCKET !== '';
}
function supabase_storage_headers(array $extra = []): array {
    $h = ['apikey: ' . SUPABASE_SERVICE_ROLE_KEY];
    // Legacy keys are JWTs and also go in Authorization. New "sb_secret_..." keys are not JWTs: apikey header only.
    if (strpos(SUPABASE_SERVICE_ROLE_KEY, 'sb_') !== 0) $h[] = 'Authorization: Bearer ' . SUPABASE_SERVICE_ROLE_KEY;
    return array_merge($h, $extra);
}
function supabase_object_path(string $key): string {
    return str_replace('%2F', '/', rawurlencode($key));
}
function supabase_object_url(string $kind, string $key): string {
    return SUPABASE_URL . '/storage/v1/object/' . $kind . rawurlencode(SUPABASE_STORAGE_BUCKET) . '/' . supabase_object_path($key);
}

/** Stores an uploaded report. Returns false (and logs why) if it could not be stored. */
function store_report(string $tmpPath, string $key, string $mime = 'application/octet-stream'): bool {
    if (storage_bucket_is_supabase()) {
        $data = file_get_contents($tmpPath);
        if ($data === false) return false;
        $r = http_json('POST', supabase_object_url('', $key),
            supabase_storage_headers(['Content-Type: ' . $mime, 'x-upsert: false']), $data, 30);
        if ($r['code'] >= 200 && $r['code'] < 300) return true;
        // No silent fallback to local disk: Render's disk is wiped on every deploy/restart.
        error_log('Supabase upload failed: HTTP ' . $r['code'] . ' ' . $r['error'] . ' ' . $r['body']);
        return false;
    }
    if (S3_BUCKET !== '' && class_exists('Aws\\S3\\S3Client')) {
        try {
            $c = new Aws\S3\S3Client(['version' => 'latest', 'region' => AWS_REGION]);
            $c->putObject(['Bucket' => S3_BUCKET, 'Key' => $key, 'SourceFile' => $tmpPath, 'ServerSideEncryption' => 'AES256']);
            return true;
        } catch (Throwable $e) { error_log('S3 error: ' . $e->getMessage()); return false; }
    }
    $dest = __DIR__ . '/../storage/' . $key;     // local development only
    if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0750, true);
    return is_uploaded_file($tmpPath) ? move_uploaded_file($tmpPath, $dest) : copy($tmpPath, $dest);
}

/** Short-lived download URL (5 min) for a stored report, or null if unavailable. */
function report_url(string $key): ?string {
    if (storage_bucket_is_supabase()) {
        $r = http_json('POST', supabase_object_url('sign/', $key),
            supabase_storage_headers(['Content-Type: application/json']), json_encode(['expiresIn' => 300]));
        if ($r['code'] >= 200 && $r['code'] < 300) {
            $signed = json_decode($r['body'], true)['signedURL'] ?? '';
            if ($signed !== '') {
                // Supabase returns a RELATIVE path ("/object/sign/...") - it must be prefixed with <project>/storage/v1
                if (strpos($signed, 'http') === 0) return $signed;
                if (strpos($signed, '/storage/v1') === 0) return SUPABASE_URL . $signed;
                return SUPABASE_URL . '/storage/v1' . (($signed[0] ?? '') === '/' ? '' : '/') . $signed;
            }
        }
        error_log('Supabase sign failed: HTTP ' . $r['code'] . ' ' . $r['body']);
        return null;                              // never fall back to the public URL: the bucket is private
    }
    if (S3_BUCKET !== '' && class_exists('Aws\\S3\\S3Client')) {
        $c = new Aws\S3\S3Client(['version' => 'latest', 'region' => AWS_REGION]);
        $cmd = $c->getCommand('GetObject', ['Bucket' => S3_BUCKET, 'Key' => $key]);
        return (string)$c->createPresignedRequest($cmd, '+5 minutes')->getUri();
    }
    return null;
}
