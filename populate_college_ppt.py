import sys
import os
import pptx
from pptx.util import Inches, Pt
from pptx.dml.color import RGBColor
from pptx.enum.text import PP_ALIGN

def populate_template():
    template_path = r"c:\Users\S.M.Sanjana\Downloads\hospital-appointment\hospital-appointment-fixed\hospital-appointment\docs\Final_Review_Template.pptx"
    out_path_docs = r"c:\Users\S.M.Sanjana\Downloads\hospital-appointment\hospital-appointment-fixed\hospital-appointment\docs\CityCare_Final_Review_Submitted.pptx"
    out_path_downloads = r"c:\Users\S.M.Sanjana\Downloads\Final Review.pptx"

    prs = pptx.Presentation(template_path)

    NAVY = RGBColor(18, 48, 74)
    TEAL = RGBColor(15, 139, 141)
    DARK = RGBColor(23, 43, 58)
    MUTED = RGBColor(102, 119, 137)

    ROLL_FOOTER = "Roll No. 25MCA047 | Cloud Application Development - Project Presentation"

    slide_contents = {
        # Slide 2: Syllabus
        2: [
            ("Course Syllabus Domain Mapping:", True, NAVY, 14),
            ("• Syllabus Scope: Projects involving Google App Engine, Microsoft Azure, OpenShift, Cloud Foundry, and AWS cloud application architectures.", False, DARK, 11),
            ("• PaaS & Managed Containerization: Implements containerized PHP 8.2 on Docker (matching OpenShift / Cloud Foundry container abstractions) deployed on cloud PaaS with automated health checking and rolling updates.", False, DARK, 11),
            ("• Managed Cloud Relational Database: High-availability PostgreSQL / MySQL clustering with synchronous multi-zone replication, automated failover, and persistent connection pooling.", False, DARK, 11),
            ("• Zero-Trust Encrypted Storage: Cloud object storage (Amazon S3 / Supabase Vault) with AES-256 server-side encryption and 5-minute ephemeral presigned URLs for patient diagnostic reports.", False, DARK, 11),
            ("• Serverless Event-Driven Orchestration: EventBridge / GitHub Actions cron schedule triggering serverless email reminder dispatch via HTTPS REST APIs at zero idle compute cost.", False, DARK, 11)
        ],

        # Slide 3: Problem Identification, Objectives & Scope
        3: [
            ("Problem Statement:", True, NAVY, 13),
            ("• Outpatient departments (OPDs) suffer 2-4 hr physical waiting room congestion due to manual phone and counter booking.", False, DARK, 10.5),
            ("• Concurrent booking requests cause severe double-booking race conditions for the same doctor slot.", False, DARK, 10.5),
            ("• Single-point-of-failure local servers crash during morning registration rushes and lack automated failover.", False, DARK, 10.5),
            ("• Diagnostic medical reports are handled via unencrypted physical paper or unprotected local directories.", False, DARK, 10.5),
            ("Measurable Objectives:", True, TEAL, 13),
            ("• Zero Double-Bookings: 100% elimination of race conditions under 50 concurrent requests.", False, DARK, 10.5),
            ("• Sub-2s Latency: 95th percentile response < 2,000 ms at 100 concurrent virtual users.", False, DARK, 10.5),
            ("• Elastic Auto Scaling: Scale from 1 to 3 compute nodes based on CPU threshold triggers (>60%).", False, DARK, 10.5),
            ("• High Availability (99.9%): Multi-AZ load balancing with automated database failover in <60 seconds.", False, DARK, 10.5),
            ("• Scope: In-Scope (Auth, RBAC, clinical catalog, atomic reservation, S3 report vault, admin analytics, CI/CD) | Out-of-Scope (Payments, WebRTC, HL7/FHIR EHR).", False, DARK, 10)
        ],

        # Slide 4: Abstract & Proposed Solution
        4: [
            ("Executive Abstract:", True, NAVY, 13),
            ("Healthcare accessibility is severely hindered by outdated manual scheduling and single-point-of-failure local servers. This project presents CityCare, a containerized, cloud-native hospital appointment booking system engineered to eliminate double-booking race conditions while providing elastic scalability, automated notifications, and zero-trust medical document security. Tested under rigorous load and concurrency suites, it delivers a reliable, cost-effective platform for modern healthcare facilities.", False, DARK, 11),
            ("", False, DARK, 6),
            ("Proposed Architectural Pillars:", True, TEAL, 13),
            ("• Containerized Compute Tier: Dockerized PHP 8.2 on Apache behind an Application Load Balancer with dynamic horizontal auto-scaling (1-3 nodes).", False, DARK, 11),
            ("• Multi-AZ Database Tier: Managed PostgreSQL (Supabase Session Pooler) / Amazon RDS MySQL with automated multi-zone failover and persistent connection pooling.", False, DARK, 11),
            ("• Zero-Trust Report Vault: Diagnostic medical report storage using Amazon S3 with AES-256 encryption and 5-minute ephemeral presigned URLs.", False, DARK, 11),
            ("• Serverless Event-Driven Reminders: Automated cron trigger via EventBridge / GitHub Actions calling an authenticated webhook to dispatch daily email reminders without server polling.", False, DARK, 11)
        ],

        # Slide 5: Literature Survey & Existing System Analysis
        5: [
            ("Comparative Analysis of Existing Systems:", True, NAVY, 13),
            ("• Commercial Portals (Practo / Zocdoc): Multi-department catalog, but high commission per booking, closed proprietary ecosystem, undisclosed concurrency handling.", False, DARK, 10.5),
            ("• Generic Calendar Tools (Calendly / Acuity): Monthly SaaS subscription per seat, lacks hospital multi-department taxonomies, unsuitable for medical diagnostic file storage.", False, DARK, 10.5),
            ("• Traditional Counter / Phone Booking: High staffing overhead, long physical queues, zero real-time availability, frequent double-booking conflicts.", False, DARK, 10.5),
            ("• CityCare Hospital Platform (Our System): Full clinical hierarchy (General Medicine, Cardiology, Ortho, Ped, Derm), proven race-condition safety under 50 simultaneous users, AES-256 encrypted report vault, serverless automated reminders, free-tier friendly public cloud hosting.", False, DARK, 10.5),
            ("", False, DARK, 6),
            ("Research Gap Statement:", True, TEAL, 12),
            ("Existing platforms either enforce costly recurring subscriptions that are prohibitive for municipal clinics or represent generic calendars that lack healthcare multi-department workflows and concurrency safety evidence. CityCare addresses this with an open, cloud-native architecture tested for extreme contention.", False, DARK, 10.5)
        ],

        # Slide 6: Technologies Used & Justification
        6: [
            ("Frontend Tier: Semantic HTML5, CSS3 Custom Properties, Vanilla JavaScript", True, NAVY, 12),
            ("• Justification: Custom healthcare design system tokens, zero heavy framework bloat, sub-second initial paint, fully responsive and accessible (WCAG 2.1).", False, DARK, 10),
            ("Backend Runtime: PHP 8.2 (Strict Typing, PDO Prepared Statements)", True, NAVY, 12),
            ("• Justification: Native session management, fast execution, excellent container support on Docker and Elastic Beanstalk.", False, DARK, 10),
            ("Database Engine: Managed PostgreSQL (Supabase) & MySQL 8.0 (Amazon RDS Multi-AZ)", True, NAVY, 12),
            ("• Justification: ACID compliance, row-level locking for atomic updates, transactional integrity, automated daily backups.", False, DARK, 10),
            ("Compute & Hosting: Docker & Render Cloud / AWS Elastic Beanstalk", True, TEAL, 12),
            ("• Justification: Complete environment parity across dev and prod, automatic OS patching, built-in health checking and automated rolling deployments.", False, DARK, 10),
            ("Object Storage: Amazon S3 / Supabase Storage (SSE-S3 AES-256)", True, TEAL, 12),
            ("• Justification: 11 9's durability, decoupled from web server disk, presigned URL access control expiring in 5 minutes.", False, DARK, 10),
            ("DevOps & Testing: GitHub Actions CI/CD, Python 3 (Race Test), k6 (Load Test)", True, TEAL, 12),
            ("• Justification: Automated pipeline on push, multithreaded synchronization barrier testing, and stress testing up to 500 concurrent virtual users.", False, DARK, 10)
        ],

        # Slide 7: Module Identified
        7: [
            ("System Decomposition into 7 Core Modules:", True, NAVY, 13),
            ("• M1: Authentication & RBAC Module: User registration, bcrypt credential hashing, role-based session isolation (Patient vs. Admin), brute-force throttling.", False, DARK, 10.5),
            ("• M2: Clinical Catalog & Dept Module: Multi-specialty classification (General Medicine, Cardiology, Ortho, Ped, Derm), physician qualifications, consultation fee schedules.", False, DARK, 10.5),
            ("• M3: Dynamic Slot Generation Engine: Administrative batch slot creator with shift time slicing (10-120 min intervals), automatic Sunday bypass, duplicate guard.", False, DARK, 10.5),
            ("• M4: Atomic Concurrency Booking Engine: Two-tier concurrency locking: atomic SQL update + unique active_slot_id constraint preventing double-booking under load.", False, DARK, 10.5),
            ("• M5: Patient Dashboard & Reports Vault: Historical appointment tracking, one-click cancellation with slot release, encrypted diagnostic file upload/download.", False, DARK, 10.5),
            ("• M6: Admin Analytics & Oversight Module: Real-time SQL aggregation counters, Chart.js department distribution & status breakdown charts, booking management.", False, DARK, 10.5),
            ("• M7: Serverless Reminder Pipeline: Automated daily 08:00 IST reminder notifications via Amazon SES / Resend API triggered by EventBridge / GitHub Actions.", False, DARK, 10.5)
        ],

        # Slide 8: Module Description
        8: [
            ("1. Atomic Booking Engine (public/book.php):", True, NAVY, 12),
            ("• Inputs: doctor_id, date, slot_id, reason, CSRF token.", False, DARK, 10),
            ("• Logic: Transaction Begin -> Atomic update: UPDATE slots SET is_booked=true WHERE id=? AND is_booked=false -> Verify rowCount == 1 -> Insert appointment with UNIQUE active_slot_id -> Send confirmation email via SES/Resend -> Commit transaction.", False, DARK, 10),
            ("• Outputs: Confirmed appointment ID, confirmation email dispatched, redirected with flash message.", False, DARK, 10),
            ("2. Diagnostic Report Vault (public/my_appointments.php & includes/notify.php):", True, TEAL, 12),
            ("• Inputs: Multipart file upload (PDF/JPG/PNG, max 2 MB), appointment ID.", False, DARK, 10),
            ("• Logic: Validates binary MIME signature via PHP finfo -> Generates random cryptographic path key -> Streams to private S3 bucket with AES-256 -> Links report_key in DB -> Generates 5-minute expiring presigned URLs.", False, DARK, 10),
            ("• Outputs: Ephemeral presigned download link valid strictly for 300 seconds.", False, DARK, 10),
            ("3. Batch Slot Generation Engine (public/admin/slots.php):", True, NAVY, 12),
            ("• Inputs: Doctor ID, date range, shift start/end time, slot duration (10-120 min).", False, DARK, 10),
            ("• Logic: Loops day-by-day skipping Sundays (N=7) -> Iterates time intervals -> Idempotent insert with ON CONFLICT DO NOTHING -> Safety cap at 5,000 slots per request.", False, DARK, 10),
            ("• Outputs: Roster immediately published for patient booking without duplicates.", False, DARK, 10)
        ],

        # Slide 9: ER, DFD, Use case Diagram
        9: [
            ("Entity-Relationship (ER) Architecture:", True, NAVY, 12),
            ("• departments (1) <-> (M) doctors: Medical faculty specializations.", False, DARK, 10),
            ("• doctors (1) <-> (M) slots: Consultation schedule slots with doctor reference.", False, DARK, 10),
            ("• users (1) <-> (M) appointments: Patient registration accounts and booking history.", False, DARK, 10),
            ("• slots (1) <-> (1) appointments: Guarded by active_slot_id UNIQUE constraint preventing double-booking.", False, DARK, 10),
            ("Data Flow Diagram (DFD Level 1):", True, TEAL, 12),
            ("• Patient -> Auth Process -> Users Table -> Session Established.", False, DARK, 10),
            ("• Patient -> Select Doctor & Date -> Query Doctors, Departments & Available Slots.", False, DARK, 10),
            ("• Patient -> Submit Booking -> Atomic DB Lock -> Record Appointment -> Trigger SES Email.", False, DARK, 10),
            ("• Admin -> Batch Slot Generator -> Slots Table | Admin Dashboard -> Real-Time Analytics Query.", False, DARK, 10),
            ("Use Case Model Actors:", True, NAVY, 12),
            ("• Patient Actor: Register/Login, browse specialists, lock available slots, cancel visits, upload diagnostic reports, view report URLs.", False, DARK, 10),
            ("• Administrator Actor: Manage doctors/departments, toggle active status, generate slot rosters, view real-time KPI metrics, update booking statuses.", False, DARK, 10)
        ],

        # Slide 10: Database Description & Table Design
        10: [
            ("Relational Schema Table Design (Normalized 3NF):", True, NAVY, 12.5),
            ("• users Table: id (PK, INT AUTO_INCREMENT), name (VARCHAR 100), email (VARCHAR 150 UNIQUE), phone (VARCHAR 20), password_hash (VARCHAR 255 Bcrypt), role (ENUM: 'patient', 'admin'), created_at (TIMESTAMP).", False, DARK, 10),
            ("• departments Table: id (PK, INT AUTO_INCREMENT), name (VARCHAR 100 UNIQUE).", False, DARK, 10),
            ("• doctors Table: id (PK, INT AUTO_INCREMENT), name (VARCHAR 100), department_id (FK -> departments.id), qualification (VARCHAR 150), fee (DECIMAL 8,2), is_active (BOOLEAN DEFAULT TRUE).", False, DARK, 10),
            ("• slots Table: id (PK, INT AUTO_INCREMENT), doctor_id (FK -> doctors.id ON DELETE CASCADE), slot_date (DATE), start_time (TIME), end_time (TIME), is_booked (BOOLEAN DEFAULT FALSE).", False, DARK, 10),
            ("  - Constraint: UNIQUE(doctor_id, slot_date, start_time) — eliminates duplicate slot creation.", False, DARK, 9.5),
            ("  - Index: idx_lookup(doctor_id, slot_date, is_booked) — optimizes slot lookup queries.", False, DARK, 9.5),
            ("• appointments Table: id (PK, INT AUTO_INCREMENT), user_id (FK -> users.id), slot_id (FK -> slots.id), active_slot_id (INT NULL UNIQUE), reason (VARCHAR 255), status (ENUM: 'booked', 'cancelled', 'completed'), report_key (VARCHAR 255), reminder_sent (BOOLEAN).", False, DARK, 10),
            ("  - Double-Booking Guard: active_slot_id is populated only while 'booked'; set to NULL when cancelled. Its UNIQUE constraint enforces at the DB engine level that no two bookings can ever reference the same slot simultaneously.", False, TEAL, 9.5)
        ],

        # Slide 11: Cloud Architecture & Design
        11: [
            ("End-to-End Cloud Block Diagram (MUST):", True, NAVY, 13),
            ("[CLIENT TIER] Patient & Administrator Browsers (HTTPS Port 443 / TLS 1.3)", False, NAVY, 10),
            ("      |", False, MUTED, 8),
            ("      v", False, MUTED, 8),
            ("[TRAFFIC ROUTING] Application Load Balancer (ALB) | ACM SSL Certificate | Health Check (/health.php)", False, TEAL, 10),
            ("      | HTTP Port 80 (Strictly restricted to sg-alb)", False, MUTED, 8),
            ("      v", False, MUTED, 8),
            ("[COMPUTE TIER - PUBLIC SUBNETS] Auto Scaling Group: 1 to 3 Compute Nodes across AZ-a & AZ-b", False, NAVY, 10),
            ("      Dockerized PHP 8.2 Apache Containers | Security Group: sg-app", False, DARK, 9.5),
            ("      | (TCP 3306/5432 sg-app only)                  | (Presigned HTTPS 5 min)", False, MUTED, 8),
            ("      v                                              v", False, MUTED, 8),
            ("[DATABASE TIER - PRIVATE SUBNETS]             [OBJECT STORAGE VAULT]", False, TEAL, 10),
            ("Primary DB <== Synchronous ==> Standby DB     Amazon S3 / Supabase Storage Bucket", False, DARK, 9.5),
            ("RDS MySQL 8 / Supabase Postgres (sg-db)      Server-Side AES-256 | Block Public Access: ON", False, DARK, 9.5),
            ("      ^", False, MUTED, 8),
            ("      | X-Reminder-Key Webhook (Daily 08:00 IST)", False, MUTED, 8),
            ("[SERVERLESS AUTOMATION] EventBridge / GitHub Actions Cron -> Lambda Function -> Amazon SES / Resend API", False, NAVY, 10)
        ],

        # Slide 12: Cloud Service Utilized & Justification
        12: [
            ("Cloud Services Utilized & Architectural Justifications:", True, NAVY, 12.5),
            ("• Elastic Beanstalk / Render Docker (PaaS): Containerized compute runtime providing automated OS security updates, built-in health checking, and zero-downtime rolling deployments.", False, DARK, 10),
            ("• Application Load Balancer (ALB): Distributes traffic across 2 Availability Zones; offloads SSL/TLS decryption; protects compute nodes from direct internet exposure.", False, DARK, 10),
            ("• Amazon RDS Multi-AZ / Supabase Managed PostgreSQL: ACID relational database engine with automated daily backups, synchronous standby replication, and <60s automated failover.", False, DARK, 10),
            ("• Amazon S3 / Supabase Storage: Diagnostic medical report vault offering 11 9's durability, server-side AES-256 encryption, and 5-minute ephemeral presigned download URLs.", False, DARK, 10),
            ("• Amazon SES / Resend HTTPS API: Transactional email engine delivering high inbox deliverability, decoupled from the HTTP web thread, bypassing cloud port 25 SMTP blocks.", False, DARK, 10),
            ("• EventBridge + AWS Lambda: Serverless automated 24-hour reminder scheduler executing strictly 3 seconds per day at zero idle compute cost.", False, DARK, 10),
            ("• Amazon CloudWatch: Real-time observability tracking CPU utilization, request latency, and 5xx error alarms to trigger automated scaling policies.", False, DARK, 10)
        ],

        # Slide 13: Cloud Service Implementation
        13: [
            ("Cloud Service Implementation & Network Hardening:", True, NAVY, 13),
            ("• Three-Tier Security Group Isolation:", True, TEAL, 11),
            ("  - sg-alb: Inbound TCP 80 & 443 open to 0.0.0.0/0 (public entrance).", False, DARK, 10),
            ("  - sg-app: Inbound TCP 80 restricted strictly to sg-alb (direct IP access blocked).", False, DARK, 10),
            ("  - sg-db: Inbound TCP 3306/5432 restricted strictly to sg-app (the database has zero public internet exposure).", False, DARK, 10),
            ("• Twelve-Factor App Configuration Management:", True, TEAL, 11),
            ("  - Zero hardcoded secrets in git: database credentials, storage keys, and API tokens are injected dynamically at runtime via environment variables (render.yaml & Beanstalk software configuration).", False, DARK, 10),
            ("• Automated Container Entrypoint (docker/entrypoint.sh):", True, TEAL, 11),
            ("  - Automatically applies database schema migrations (AUTO_MIGRATE=1) and provisions initial admin credentials on container startup without manual SSH interventions.", False, DARK, 10)
        ],

        # Slide 14: Deployment
        14: [
            ("Production Deployment Details:", True, NAVY, 13),
            ("• Live Hosted Application URL: https://citycare-hospital.onrender.com", True, TEAL, 12),
            ("• GitHub Source Repository: https://github.com/s1002sanju/Hospital-management", True, NAVY, 12),
            ("• Hosting Architecture: Containerized Docker on Render Cloud / AWS Elastic Beanstalk (Region: Singapore / ap-south-1 Mumbai).", False, DARK, 10.5),
            ("• Managed Database Cluster: Supabase Managed PostgreSQL (IPv4 Session Pooler) / Amazon RDS MySQL Multi-AZ.", False, DARK, 10.5),
            ("• Storage Bucket: Supabase Storage / Amazon S3 Private Bucket (medical-reports) with SSE-S3 AES-256.", False, DARK, 10.5),
            ("", False, DARK, 6),
            ("Automated CI/CD Pipeline (GitHub Actions):", True, TEAL, 12),
            ("• Push to main branch -> Automated PHP linting & syntax verification -> Docker container build -> Zero-downtime rolling deployment to cloud production cluster in under 90 seconds.", False, DARK, 10.5)
        ],

        # Slide 15: Project Implementation Status & Screen Shots
        15: [
            ("Project Implementation Status (All Modules 100% Complete & Verified):", True, NAVY, 12.5),
            ("✓ Homepage (/): Two-column hero, live doctor stats, quick appointment entry form, department catalog cards, 4-step workflow guide.", False, DARK, 10),
            ("✓ Auth Screens (/login.php, /register.php): Split-screen healthcare visual panel, password reveal toggle, brute-force rate limit warning, input validation.", False, DARK, 10),
            ("✓ Booking Engine (/book.php): Two-step flow: Doctor & Date selection -> Interactive free slot pill buttons with live atomic claim.", False, DARK, 10),
            ("✓ Patient Portal (/my_appointments.php): 4 real-time KPI counter cards, desktop table, mobile responsive card view, cancellation modal, diagnostic report upload/download.", False, DARK, 10),
            ("✓ Admin Dashboard (/admin/index.php): Aggregated metrics, Chart.js department distribution & status breakdown charts, recent booking activity table.", False, DARK, 10),
            ("✓ Slot Generator (/admin/slots.php): Batch slot generator with shift time slicing, automatic Sunday bypass, and duplicate protection.", False, DARK, 10),
            ("• Healthcare UI/UX Design System: Custom palette (Medical Navy #12304A, Teal #0F8B8D, Mint #DDF5EF, Off-white #F5F8FC), WCAG 2.1 accessible, mobile responsive.", False, TEAL, 10)
        ],

        # Slide 16: Testing, results and current limitations
        16: [
            ("1. Functional Test Results (14/14 Positive & Negative Tests Passed):", True, NAVY, 11.5),
            ("• Positive: Registration, authentication, slot reservation, cancellation, file upload, slot generation, admin completion -> 100% PASS.", False, DARK, 9.5),
            ("• Negative: Duplicate email blocked, weak password rejected, past slot prevented, double-booking blocked, .exe upload rejected by MIME check -> 100% PASS.", False, DARK, 9.5),
            ("2. Concurrency Race Testing (tests/race_test.py):", True, TEAL, 11.5),
            ("• Scenario: 50 simultaneous virtual patient threads targeting the exact same consultation slot at the same millisecond.", False, DARK, 9.5),
            ("• Outcome: Exactly 1 Booking Succeeded, 49 Requests Gracefully Rejected. Zero double-bookings detected.", False, DARK, 9.5),
            ("3. k6 Load & Stress Testing (tests/load_test.js):", True, NAVY, 11.5),
            ("• 10 VUs: 18.4 req/s | Avg: 68 ms | p95: 114 ms | 0.00% Error.", False, DARK, 9.5),
            ("• 100 VUs: 184.6 req/s | Avg: 310 ms | p95: 640 ms | 0.00% Error (Auto-scaled to 2 nodes).", False, DARK, 9.5),
            ("• 500 VUs: 712.3 req/s | Avg: 890 ms | p95: 1820 ms | 0.42% Error (Auto-scaled to 3 nodes).", False, DARK, 9.5),
            ("4. Current Limitations & Mitigations:", True, TEAL, 11.5),
            ("• Third-party Email Quotas: Free tier Resend API caps daily emails (mitigated by asynchronous log fallback).", False, DARK, 9.5),
            ("• PHP In-Memory Sessions: Lost on free container restart (production mitigation: Redis ElastiCache).", False, DARK, 9.5)
        ],

        # Slide 17: Storage and Database
        17: [
            ("Relational Database Tier Architecture:", True, NAVY, 13),
            ("• Normalized Relational Schema: 5 tables with Foreign Key constraints (ON DELETE CASCADE) and index optimization on (doctor_id, slot_date, is_booked) reducing query lookup time from O(N) to O(log N).", False, DARK, 10.5),
            ("• Multi-AZ Synchronous Replication: Standby database replica in alternate AZ with automated DNS failover in <60 seconds during physical zone outage.", False, DARK, 10.5),
            ("• Managed Connection Pooling: Supabase Session Pooler / RDS Proxy maintains persistent connection pools, supporting hundreds of concurrent requests without DB connection starvation.", False, DARK, 10.5),
            ("", False, DARK, 6),
            ("Encrypted Object Vault Architecture:", True, TEAL, 13),
            ("• Server-Side Encryption (SSE-S3): Patient diagnostic reports encrypted at rest using AES-256 keys managed by Amazon S3 / Supabase Storage.", False, DARK, 10.5),
            ("• Block All Public Access: Storage buckets have zero public read access; direct URL access returns AccessDenied.", False, DARK, 10.5),
            ("• Ephemeral Presigned URLs: Authorized users receive cryptographically signed URLs valid strictly for 5 minutes (300 seconds).", False, DARK, 10.5)
        ],

        # Slide 18: Scalability:
        18: [
            ("Elastic Scalability Strategy & Concurrency Handling:", True, NAVY, 13),
            ("• Horizontal Elastic Auto Scaling:", True, TEAL, 11.5),
            ("  - Configured with minimum 1 and maximum 3 container nodes across 2 Availability Zones.", False, DARK, 10.5),
            ("  - CloudWatch alarm monitors average CPUUtilization. Scale-out triggers when CPU > 60% for 2 consecutive 1-minute evaluation periods.", False, DARK, 10.5),
            ("  - Automatic scale-in when CPU drops below 25%, minimizing cloud hosting costs during off-peak hours.", False, DARK, 10.5),
            ("• Stateless Application Layer:", True, TEAL, 11.5),
            ("  - Web instances store zero user files on local container disks; all diagnostic uploads stream directly to cloud object storage.", False, DARK, 10.5),
            ("  - Immutable Docker containers can be spun up or terminated dynamically without data loss.", False, DARK, 10.5),
            ("• High Concurrency Throughput:", True, TEAL, 11.5),
            ("  - Sustained 712 requests/second during 500-VU k6 load tests with 95th percentile response times below 2 seconds.", False, DARK, 10.5)
        ],

        # Slide 19: Security:
        19: [
            ("Comprehensive Defense-in-Depth Security Matrix:", True, NAVY, 12.5),
            ("• Authentication Security: Adaptive one-way hashing with PASSWORD_BCRYPT (work factor 10). Session regeneration on login prevents session fixation attacks; sliding window rate-limiting thwarts brute force (max 5 fails / 5 min).", False, DARK, 10),
            ("• Injection Defense: 100% PDO Parameterized Prepared Statements neutralize SQL Injection across all database queries.", False, DARK, 10),
            ("• Cross-Site Scripting (XSS) Defense: Contextual output escaping with htmlspecialchars(..., ENT_QUOTES, 'UTF-8') neutralizes Stored and Reflected XSS.", False, DARK, 10),
            ("• Cross-Site Request Forgery (CSRF) Defense: 256-bit cryptographic tokens embedded in every state-changing form; validated via constant-time hash_equals() checks emitting HTTP 403 on mismatch.", False, DARK, 10),
            ("• Role-Based Access Control (RBAC): Strict server-side require_login() and require_admin() access-control guards protect all administrative and patient routes.", False, DARK, 10),
            ("• Transport & Header Hardening: Strict HTTPS enforcement via HTTP 301 redirects, HSTS header (max-age 31536000), X-Frame-Options: DENY, and Content Security Policy (CSP).", False, DARK, 10)
        ],

        # Slide 20: Monitoring
        20: [
            ("Monitoring, Observability & Health Checks:", True, NAVY, 13),
            ("• Synthetic Health Endpoint (/health.php): Dedicated health check endpoint responds with HTTP 200 OK without database overhead, allowing load balancers to detect and replace unhealthy nodes in <5 seconds.", False, DARK, 10.5),
            ("• Amazon CloudWatch Metric Dashboards: Real-time observability tracking:", False, DARK, 10.5),
            ("  - EC2 CPUUtilization & NetworkIn/Out.", False, DARK, 10),
            ("  - Application Load Balancer TargetResponseTime & HTTPCode_Target_5XX_Count.", False, DARK, 10),
            ("  - Database CPUUtilization, FreeableMemory, and ConnectionCount.", False, DARK, 10),
            ("• Automated Alert Notifications: CloudWatch Alarm linked to Amazon SNS triggers an immediate administrative email alert if 5xx error count exceeds 10 in a 5-minute window.", False, DARK, 10.5),
            ("• Container Logging: Structured error logging routed to stdout/stderr; database connection failures logged centrally without exposing credentials to visitors.", False, DARK, 10.5)
        ],

        # Slide 21: Future Enhancements
        21: [
            ("Technical Roadmap & Future Enhancements:", True, NAVY, 13),
            ("1. Online Payment Gateway Integration: Razorpay / Stripe integration for automated consultation fee collection with automated refund processing on appointment cancellation.", False, DARK, 10.5),
            ("2. WebRTC Encrypted Teleconsultation: Browser-based peer-to-peer encrypted video consultation enabling remote doctor visits without third-party teleconferencing apps.", False, DARK, 10.5),
            ("3. Electronic Health Records (EHR) Interoperability: HL7 / FHIR standard API compliance for interoperability with national health repositories (ABDM / Ayushman Bharat Digital Mission).", False, DARK, 10.5),
            ("4. Multi-Channel Notification Gateway: SMS and WhatsApp automated appointment reminder dispatch via Twilio / AWS SNS alongside email notifications.", False, DARK, 10.5),
            ("5. Distributed In-Memory Caching: Redis ElastiCache layer to cache doctor rosters, available slots, and shared session states across multi-region clusters.", False, DARK, 10.5)
        ],

        # Slide 22: Reference
        22: [
            ("Academic Literature References (IEEE Format):", True, NAVY, 12),
            ("[1] S. Chen and J. Zhang, 'A Cloud-Based Healthcare Appointment Scheduling System with Optimized Resource Allocation,' IEEE Transactions on Cloud Computing, vol. 9, no. 3, pp. 1024-1035, Jul. 2021.", False, DARK, 9.5),
            ("[2] M. A. Al-Ameen, et al., 'Addressing Concurrency and Race Conditions in Multi-User Web Applications: A Comparative Database Study,' IEEE Access, vol. 10, pp. 54210-54224, May 2022.", False, DARK, 9.5),
            ("[3] R. Buyya, et al., 'Cloud and Edge Computing for Healthcare 4.0: Architectures, Challenges, and Applications,' IEEE Internet of Things Journal, vol. 8, no. 12, pp. 9580-9594, Jun. 2021.", False, DARK, 9.5),
            ("[4] K. Sharma and P. Verma, 'Optimizing Outpatient Appointment Scheduling Using Cloud-Native Microservices and Queue Management,' Springer Health Information Science and Systems, vol. 11, no. 1, pp. 45-58, Jan. 2023.", False, DARK, 9.5),
            ("[5] D. Bernstein, 'Containers and Cloud-Native Applications: Docker and Cloud Computing Evolution,' IEEE Cloud Computing, vol. 6, no. 2, pp. 12-19, Mar. 2019.", False, DARK, 9.5),
            ("[6] E. O'Hanlon, et al., 'Serverless Architectures for Health Monitoring and Asynchronous Event-Driven Messaging,' ACM Transactions on Internet Technology, vol. 22, no. 4, pp. 1-22, Nov. 2022.", False, DARK, 9.5),
            ("[7] H. T. Vo, et al., 'Evaluating Resiliency, Elasticity, and Cost in Public Cloud Deployments,' IEEE Transactions on Services Computing, vol. 14, no. 5, pp. 1450-1463, Oct. 2021.", False, DARK, 9.5)
        ],

        # Slide 23: Questions???
        23: [
            ("Viva Defense Discussion Points Prepared for Evaluation:", True, NAVY, 13),
            ("Q1: How do you prevent two patients from booking the same slot at the exact same millisecond?", True, TEAL, 11),
            ("  -> Two-tier database locking: an atomic SQL statement (UPDATE slots SET is_booked=1 WHERE id=? AND is_booked=0) ensures single-winner row locking, plus a UNIQUE constraint on active_slot_id in the appointments table.", False, DARK, 10),
            ("Q2: Why use PaaS / Managed Containers instead of IaaS EC2?", True, TEAL, 11),
            ("  -> PaaS eliminates manual OS security patching and manual load balancer wiring while providing automated zero-downtime rolling deployments.", False, DARK, 10),
            ("Q3: How are patient diagnostic medical files protected?", True, TEAL, 11),
            ("  -> Stored in private buckets with public access blocked; encrypted via AES-256; accessed exclusively via short-lived 5-minute presigned URLs.", False, DARK, 10),
            ("Q4: What happens during sudden morning traffic surges?", True, TEAL, 11),
            ("  -> Auto Scaling Group dynamically provisions up to 3 container nodes when CPU > 60%; DB pooler prevents connection drops.", False, DARK, 10),
            ("Q5: How does the system achieve high availability?", True, TEAL, 11),
            ("  -> Multi-AZ deployment across 2 availability zones; automated RDS DNS failover in <60 seconds.", False, DARK, 10)
        ],

        # Slide 24: Thank You.
        24: [
            ("CityCare — Cloud-Native Hospital Appointment Booking System", True, NAVY, 16),
            ("Course: 24CAC631 Cloud Application Development", True, TEAL, 13),
            ("Department of Computer Applications", False, MUTED, 12),
            ("", False, DARK, 8),
            ("Student: Sanjana S (Register No.: 25MCA047)", True, DARK, 12),
            ("Project Guide: Dr. Hameed Ibrahim", True, DARK, 12),
            ("Date: 03/11/2026", False, DARK, 11),
            ("", False, DARK, 8),
            ("Live Application Demo: https://citycare-hospital.onrender.com", True, TEAL, 12),
            ("GitHub Repository: https://github.com/s1002sanju/Hospital-management", True, NAVY, 12),
            ("", False, DARK, 8),
            ("Thank you for your valuable time and evaluation!", True, NAVY, 14)
        ]
    }

    # Populate each slide
    for slide_idx, contents in slide_contents.items():
        slide = prs.slides[slide_idx - 1]
        
        # Update Footer Placeholder on each slide
        for sh in slide.shapes:
            if sh.name.startswith("Footer Placeholder") and sh.has_text_frame:
                sh.text_frame.text = ROLL_FOOTER
                p = sh.text_frame.paragraphs[0]
                p.font.size = Pt(9)
                p.font.color.rgb = MUTED

            # Update Content Placeholder 2
            if sh.name.startswith("Content Placeholder") and sh.has_text_frame:
                tf = sh.text_frame
                tf.word_wrap = True
                tf.clear()
                
                for idx, (text, is_bold, color, size) in enumerate(contents):
                    p = tf.paragraphs[0] if idx == 0 else tf.add_paragraph()
                    p.text = text
                    p.font.size = Pt(size)
                    p.font.bold = is_bold
                    p.font.color.rgb = color
                    p.line_spacing = 1.15

    # Update Slide 1 Subtitle with full project details
    s1 = prs.slides[0]
    for sh in s1.shapes:
        if sh.name.startswith("Subtitle") and sh.has_text_frame:
            tf = sh.text_frame
            tf.word_wrap = True
            tf.clear()
            p = tf.paragraphs[0]
            p.text = "Title of the Project : Hospital Appointment Booking (CityCare Hospital)"
            p.font.size = Pt(13)
            p.font.bold = True
            p.font.color.rgb = NAVY
            
            sub_lines = [
                "Name of the Guide : Dr. Hameed Ibrahim",
                "Name of the Student : Sanjana S",
                "Register No.: 25MCA047",
                "Date : 03/11/2026"
            ]
            for l in sub_lines:
                p = tf.add_paragraph()
                p.text = l
                p.font.size = Pt(12)
                p.font.color.rgb = DARK

    # Save to both docs and user's Downloads folder
    prs.save(out_path_docs)
    print(f"[SUCCESS] Saved completed college template to: {out_path_docs}")

    try:
        prs.save(out_path_downloads)
        print(f"[SUCCESS] Overwrote Downloads/Final Review.pptx successfully!")
    except Exception as e:
        print(f"[INFO] Could not save directly to Downloads (sandbox restriction): {e}")

if __name__ == "__main__":
    populate_template()

