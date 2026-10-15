# Security test checklist (record result + screenshot for each)
| # | Test | How | Expected | Result |
|---|------|-----|----------|--------|
| S1 | SQL injection on login | email = `' OR 1=1 --` | Login fails (prepared statements) | |
| S2 | XSS in name/reason | register name `<script>alert(1)</script>` | Shown as plain text (escaped) | |
| S3 | CSRF | POST /book.php without token | HTTP 403 | |
| S4 | Admin URL as patient | open /admin/index.php logged in as patient | HTTP 403 | |
| S5 | Open another patient's report | /report.php?id=<other id> | 404 | |
| S6 | Brute force login | 6 wrong passwords | "Too many attempts" | |
| S7 | HTTP -> HTTPS | open http:// URL on AWS | 301 redirect to https | |
| S8 | DB public access | connect to RDS endpoint from laptop | Connection refused (private subnet) | |
| S9 | S3 public access | open object URL directly | AccessDenied | |
| S10 | OWASP ZAP baseline scan | `docker run -t zaproxy/zap-stable zap-baseline.py -t https://YOUR-URL` | No High alerts | |
