# AWS Setup – Step by Step (Region: Asia Pacific (Mumbai) ap-south-1)
Take a screenshot at the end of every step.

## 1. Network & security groups
1. VPC → use **default VPC** (fine for a student project; mention a custom VPC as future work) – note it has subnets in ≥2 AZs.
2. EC2 → Security Groups → create:
   - `sg-alb`: inbound 80 and 443 from 0.0.0.0/0.
   - `sg-app`: inbound 80 **only from `sg-alb`**.
   - `sg-db`: inbound 3306 **only from `sg-app`**.

## 2. RDS MySQL (Database)
1. RDS → Create database → MySQL 8 → **Free tier / Dev-Test** template.
2. Instance `db.t3.micro` (or t4g.micro), storage 20 GB, **encryption enabled**, automated backups 7 days.
3. Public access **No**, security group `sg-db`. For the demo of high availability, enable **Multi-AZ** briefly (costs extra – turn on only for screenshots/failover test, then off).
4. Initial DB name: leave blank (schema creates `hospital_db`). Save master user/password.
5. Load the schema: easiest way is from the Beanstalk instance via SSH/EB CLI (`eb ssh`):
   `mysql -h <rds-endpoint> -u admin -p < database/schema.sql` then `seed.sql`, then
   `DB_HOST=<endpoint> DB_USER=admin DB_PASS=... php database/create_admin.php "Admin" you@mail.com "Strong@123"`.

## 3. S3 bucket (reports)
1. S3 → Create bucket `citycare-reports-<yourname>` → **Block all public access: ON**, default encryption **SSE-S3**, versioning ON.

## 4. SES & SNS (Messaging)
1. SES → Verified identities → verify your sender email (and your test patient emails – SES starts in *sandbox*; mention it in the report).
2. SNS → Create topic `hospital-events` (Standard) → subscribe your email → confirm. Copy the Topic ARN.

## 5. IAM role for the app (least privilege)
IAM → Roles → create/extend `aws-elasticbeanstalk-ec2-role` with an inline policy allowing only:
`s3:PutObject, s3:GetObject` on `arn:aws:s3:::citycare-reports-*/*` · `ses:SendEmail` · `sns:Publish` on your topic ARN.
(Keep AWSElasticBeanstalkWebTier managed policy.)

## 6. Elastic Beanstalk (PaaS)
1. On your PC: `composer install --no-dev`, then zip the project root (not the parent folder) → `app.zip` (include `vendor/`, `.ebextensions/`).
2. Elastic Beanstalk → Create application `hospital-appointment` → Web server environment → Platform **PHP 8.x** → upload `app.zip`.
3. Configure: **Load balanced** with min 1 / max 3 instances, instance `t3.micro`; security groups `sg-app` (instances) and `sg-alb` (load balancer); health check path `/health.php`.
4. Configuration → Software → **Environment properties**:
   `DB_HOST, DB_NAME=hospital_db, DB_USER, DB_PASS, AWS_REGION=ap-south-1, SES_SENDER, SNS_TOPIC_ARN, S3_BUCKET, REMINDER_KEY=<long random>, FORCE_HTTPS=1`
5. Scaling triggers: metric CPUUtilization, upper threshold 60%, lower 25%.
6. Open the environment URL → verify the site works.

## 7. HTTPS
1. Certificate Manager → request a public certificate (needs a domain; if you have none, use a free domain/subdomain you control, or show HTTPS via the ALB with a self-signed import and explain it).
2. Load balancer listener 443 → attach certificate; keep 80 → redirect to 443 (app also redirects when `FORCE_HTTPS=1`).

## 8. Lambda + EventBridge (serverless reminders)
1. Lambda → Create function `appointment-reminder`, Python 3.12, paste `lambda/reminder_handler.py`.
2. Environment variables: `APP_URL` (https://…), `REMINDER_KEY` (same as app), `SES_SENDER`.
3. Role: add `ses:SendEmail`.
4. EventBridge → Schedule → cron `cron(30 3 * * ? *)` (09:00 IST daily) → target the Lambda.
5. Test: book an appointment for tomorrow, click **Test** in Lambda → email received → `reminder_sent=1`.

## 9. CloudWatch
1. Dashboard `hospital-dashboard` with widgets: EC2 CPUUtilization, ALB RequestCount & TargetResponseTime, RDS CPU/connections, Lambda invocations.
2. Alarm: ALB 5xx count > 10 → SNS topic (email).

## 10. CI/CD
GitHub repo → Settings → Secrets: `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY` (create an IAM user limited to Beanstalk/S3 deploy), `AWS_REGION`, `EB_BUCKET`, `EB_APP`, `EB_ENV`. Push to `main` → `.github/workflows/deploy.yml` deploys.

## 11. After the demo – avoid charges
Terminate the Beanstalk environment, delete RDS (take a final snapshot only if needed), empty/delete S3, delete the load balancer.
