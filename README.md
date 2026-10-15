# CityCare – Cloud-Based Hospital Appointment Booking System
Course: Cloud Application Development (24CAC631) · Student: Sanjana S (25MCA047)

Stack: PHP 8.1 · MySQL (Amazon RDS) · Bootstrap · AWS Elastic Beanstalk, ALB, Auto Scaling, S3, SES, SNS, Lambda, EventBridge, CloudWatch, IAM · GitHub Actions CI/CD

## Run locally (10 minutes)
1. Install PHP 8.1+ (or XAMPP) and MySQL, plus Composer.
2. `composer install`  (skip if you only want to test without AWS features)
3. `mysql -u root -p < database/schema.sql` then `mysql -u root -p < database/seed.sql`
4. `php database/create_admin.php "Admin" admin@example.com "Admin@12345"`
5. `php -S localhost:8000 -t public`
6. Open http://localhost:8000 → register a patient. Log in as admin → Slots → generate slots → patient can book.

Emails/SNS events are written to the PHP log when AWS variables are not set.

## Folders
| Path | Purpose |
|---|---|
| `public/` | Web root (patient pages, `admin/`, `api/`, `health.php`) |
| `includes/` | Security helpers (CSRF, sessions), DB connection, AWS notifications |
| `database/` | `schema.sql`, `seed.sql`, `create_admin.php` |
| `lambda/` | Daily reminder function (EventBridge → Lambda → SES) |
| `.ebextensions/`, `.github/` | Elastic Beanstalk settings and CI/CD pipeline |
| `tests/` | k6 load test, double-booking race test, security checklist |
| `docs/` | Step-by-step guide, AWS setup, literature survey, test cases, architecture |

Start with `docs/01_STEP_BY_STEP_GUIDE.md`.
"# Hospital-management" 
