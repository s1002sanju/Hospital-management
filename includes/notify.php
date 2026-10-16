<?php
// Cloud messaging helpers: Amazon SES (email to patient) and Amazon SNS (event to admin).
// If AWS is not configured (local dev) the message is written to the PHP error log instead.
function send_email(string $to, string $subject, string $body): void {
    if (SES_SENDER && class_exists('Aws\Ses\SesClient')) {
        try {
            $c = new Aws\Ses\SesClient(['version' => 'latest', 'region' => AWS_REGION]);
            $c->sendEmail([
                'Source' => SES_SENDER,
                'Destination' => ['ToAddresses' => [$to]],
                'Message' => ['Subject' => ['Data' => $subject], 'Body' => ['Text' => ['Data' => $body]]],
            ]);
        } catch (Throwable $e) { error_log('SES error: ' . $e->getMessage()); }
    } else { error_log("[EMAIL to $to] $subject :: $body"); }
}
function publish_event(string $subject, string $message): void {
    if (SNS_TOPIC_ARN && class_exists('Aws\Sns\SnsClient')) {
        try {
            $c = new Aws\Sns\SnsClient(['version' => 'latest', 'region' => AWS_REGION]);
            $c->publish(['TopicArn' => SNS_TOPIC_ARN, 'Subject' => $subject, 'Message' => $message]);
        } catch (Throwable $e) { error_log('SNS error: ' . $e->getMessage()); }
    } else { error_log("[EVENT] $subject :: $message"); }
}
function supabase_storage_headers(): array {
    $headers = [
        'apikey: ' . SUPABASE_SERVICE_ROLE_KEY,
        'Authorization: Bearer ' . SUPABASE_SERVICE_ROLE_KEY,
    ];
    return $headers;
}
function supabase_object_path(string $key): string {
    return str_replace('%2F', '/', rawurlencode($key));
}
function storage_bucket_is_supabase(): bool {
    return !empty(SUPABASE_URL) && !empty(SUPABASE_SERVICE_ROLE_KEY) && !empty(SUPABASE_STORAGE_BUCKET);
}
// Medical report storage: Supabase Storage when configured; AWS S3 next; local folder fallback.
function store_report(string $tmpPath, string $key): void {
    if (storage_bucket_is_supabase()) {
        $url = rtrim(SUPABASE_URL, '/') . '/storage/v1/object/' . rawurlencode(SUPABASE_STORAGE_BUCKET) . '/' . supabase_object_path($key);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => ['file' => new CURLFile($tmpPath)],
            CURLOPT_HTTPHEADER => supabase_storage_headers(),
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode >= 200 && $httpCode < 300) {
            return;
        }
        error_log('Supabase upload failed: HTTP ' . $httpCode . ' ' . var_export($response, true));
    }
    if (S3_BUCKET && class_exists('Aws\S3\S3Client')) {
        $c = new Aws\S3\S3Client(['version' => 'latest', 'region' => AWS_REGION]);
        $c->putObject(['Bucket' => S3_BUCKET, 'Key' => $key, 'SourceFile' => $tmpPath, 'ServerSideEncryption' => 'AES256']);
        return;
    }
    $dest = __DIR__ . '/../storage/' . $key;
    if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0750, true);
    move_uploaded_file($tmpPath, $dest);
}
function report_url(string $key): ?string {
    if (storage_bucket_is_supabase()) {
        $url = rtrim(SUPABASE_URL, '/') . '/storage/v1/object/public/' . rawurlencode(SUPABASE_STORAGE_BUCKET) . '/' . supabase_object_path($key);
        $ch = curl_init(rtrim(SUPABASE_URL, '/') . '/storage/v1/object/sign/' . rawurlencode(SUPABASE_STORAGE_BUCKET) . '/' . supabase_object_path($key));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array_merge(supabase_storage_headers(), ['Content-Type: application/json']),
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['expiresIn' => 300]),
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode >= 200 && $httpCode < 300) {
            $payload = json_decode($response, true);
            if (!empty($payload['signedURL'])) {
                return $payload['signedURL'];
            }
        }
        return $url;
    }
    if (S3_BUCKET && class_exists('Aws\S3\S3Client')) {
        $c = new Aws\S3\S3Client(['version' => 'latest', 'region' => AWS_REGION]);
        $cmd = $c->getCommand('GetObject', ['Bucket' => S3_BUCKET, 'Key' => $key]);
        return (string)$c->createPresignedRequest($cmd, '+5 minutes')->getUri();
    }
    return null;
}
