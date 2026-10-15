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
// Medical report storage: S3 (server-side encrypted) in the cloud, local folder in development.
function store_report(string $tmpPath, string $key): void {
    if (S3_BUCKET && class_exists('Aws\S3\S3Client')) {
        $c = new Aws\S3\S3Client(['version' => 'latest', 'region' => AWS_REGION]);
        $c->putObject(['Bucket' => S3_BUCKET, 'Key' => $key, 'SourceFile' => $tmpPath, 'ServerSideEncryption' => 'AES256']);
    } else {
        $dest = __DIR__ . '/../storage/' . $key;
        if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0750, true);
        move_uploaded_file($tmpPath, $dest);
    }
}
function report_url(string $key): ?string {
    if (S3_BUCKET && class_exists('Aws\S3\S3Client')) {
        $c = new Aws\S3\S3Client(['version' => 'latest', 'region' => AWS_REGION]);
        $cmd = $c->getCommand('GetObject', ['Bucket' => S3_BUCKET, 'Key' => $key]);
        return (string)$c->createPresignedRequest($cmd, '+5 minutes')->getUri();
    }
    return null;
}
