<?php
// All settings come from environment variables (set in Elastic Beanstalk -> Configuration -> Environment properties).
// Defaults below are for LOCAL development only.
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'hospital_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('AWS_REGION', getenv('AWS_REGION') ?: 'ap-south-1');
define('SES_SENDER', getenv('SES_SENDER') ?: '');          // verified SES email; empty = log instead of send
define('SNS_TOPIC_ARN', getenv('SNS_TOPIC_ARN') ?: '');     // admin notifications; empty = skip
define('S3_BUCKET', getenv('S3_BUCKET') ?: '');             // medical report bucket; empty = local storage/
define('REMINDER_KEY', getenv('REMINDER_KEY') ?: 'change-me-locally');
define('FORCE_HTTPS', getenv('FORCE_HTTPS') === '1');
define('APP_NAME', 'CityCare Hospital');
