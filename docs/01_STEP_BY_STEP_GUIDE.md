# Step-by-Step Guide: From Review I to Final Evaluation
Each phase lists the rubric marks it earns. Keep **screenshots + notes as you go** (evidence = marks).

## Phase 0 – Prepare (Day 1)
1. Create an AWS account (free tier) and **set a billing alarm** (Billing → Budgets → ₹500 alert).
2. Create a GitHub repo `hospital-appointment`; push this folder. Commit small and often (proof of individual contribution).
3. Install: PHP 8.1+, MySQL, Composer, Git, Node/k6, Python 3.

## Phase 1 – Local build & demo (Week 1) → Implementation 15, Review II
1. Follow `README.md` "Run locally".
2. Test the flows: register → login → book → cancel → upload report; admin: add doctor, generate slots, complete appointment, dashboard charts.
3. Take screenshots of every screen (Documentation, 1 mark).
4. Read `includes/bootstrap.php` and `public/book.php` until you can explain the atomic booking (`UPDATE ... WHERE is_booked=0`), CSRF, sessions and prepared statements. **The viva (10 marks) tests this.**

## Phase 2 – Improve Review I slides (this week) → Problem 10 + Literature 10 + Architecture
Add these slides to your PPT (templates in `docs/`):
1. Stakeholders & requirements (patients, receptionist/admin, doctors).
2. Measurable objectives (use the list in `docs/04_PROBLEM_OBJECTIVES.md`).
3. Literature comparison table (`docs/03_LITERATURE_SURVEY.md`).
4. Architecture diagram (`docs/02_ARCHITECTURE.md` → draw in draw.io or AWS icons).
5. Technology slide: replace "AWS (or any PHP-friendly host)" with the real services.
6. Updated timeline mapped to Review II / Final.

## Phase 3 – Deploy on AWS (Week 2–3) → Architecture 15 + Cloud services 10
Follow `docs/05_AWS_SETUP.md` in order: VPC/security groups → RDS → S3 → SES/SNS → Elastic Beanstalk → HTTPS → Lambda + EventBridge → CloudWatch. Screenshot every console page showing configuration.

## Phase 4 – Security hardening evidence (Week 3) → Security 10
1. Run through `tests/security_checks.md`; fill in Result column with screenshots.
2. Show IAM role for Beanstalk (least privilege), RDS in private subnet, S3 "Block public access" ON, encrypted S3/RDS, HTTPS redirect.

## Phase 5 – Scalability & performance (Week 4) → Scalability 10
1. Configure Auto Scaling min 1 / max 3 (CPU > 60% scale out).
2. Run `tests/load_test.js` with k6 (10/50/100/500 users); save the summary output.
3. Watch CloudWatch: CPU graph + instance count rising → screenshot.
4. Run `tests/race_test.py` (50 users, same slot) → exactly 1 booking succeeds.
5. Fill the results tables in `docs/06_TEST_CASES.md`. Write 5–6 lines of analysis (bottleneck? what happened at 500 users? what would you change?).

## Phase 6 – CI/CD (Week 4) → Innovation 5
Add GitHub secrets, push to `main`, show the pipeline deploying automatically (screenshot of green run + new version in Beanstalk).

## Phase 7 – Report, presentation, viva (Week 5) → Documentation 5 + Viva 10
Report chapters: 1 Introduction/Problem · 2 Literature survey · 3 Requirements · 4 Architecture & design (ER diagram, DFD, cloud diagram) · 5 Implementation · 6 Security · 7 Scalability & performance results · 8 Testing · 9 Conclusion/Future work · References · Appendix (screenshots, repo link).

Viva questions to prepare:
- Why PaaS (Beanstalk) and not EC2/Lambda? Why RDS not MySQL on EC2?
- How do you stop two patients booking one slot? (atomic UPDATE + unique `active_slot_id`)
- Why is RDS in a private subnet? What does the security group allow?
- How does Auto Scaling work and what did your load test show?
- What happens if one AZ fails? (RDS Multi-AZ failover; ALB across AZs)
- How are passwords/reports/secrets protected?
- Which part did you build yourself? (be ready to walk through code)

## Marks checklist
| Rubric item | Your evidence |
|---|---|
| Problem (10) | Slide + `04_PROBLEM_OBJECTIVES.md` |
| Literature (10) | Comparison table + 8–10 real references you find |
| Architecture (15) | Diagram + justification table |
| Cloud services (10) | Console screenshots, config |
| Implementation (15) | Working demo, GitHub |
| Security (10) | `security_checks.md` results |
| Scalability (10) | k6 results + CloudWatch |
| Testing (10) | `06_TEST_CASES.md` filled |
| Innovation (5) | Event-driven reminders, serverless Lambda, CI/CD |
| Documentation (5) | Report + diagrams |
| Viva (10) | Practise the questions above |
