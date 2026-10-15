# Problem, Stakeholders, Objectives, Scope

**Problem:** Hospital appointments are booked by phone or at the counter, causing long queues, double bookings, no live availability, missed appointments and paper records; systems hosted on a single local machine are unavailable out of hours and cannot handle rush periods.

**Stakeholders / users:** Patients (book, cancel, upload reports) · Admin/receptionist (doctors, slots, appointment status, dashboard) · Doctors (schedule visibility) · Hospital management (utilisation statistics).

## Measurable objectives
1. Let patients view live free slots and book within 3 clicks.
2. **Zero double bookings** with 50 simultaneous requests for one slot.
3. Average page response < 2 s (95th percentile) at 100 concurrent users.
4. Auto Scale from 1 to 3 instances under load without errors > 2%.
5. Achieve high availability using ALB across 2 AZs and RDS Multi-AZ.
6. Send automatic confirmation email and 24-hour reminder (serverless).
7. Protect data: bcrypt passwords, RBAC, HTTPS, encrypted storage, private database.
8. Deploy via automated CI/CD pipeline.

## Scope
In: patient registration/login, doctor & slot management, booking/cancel, report upload, admin dashboard, notifications, AWS deployment, testing.
Out (future work): online payment, video consultation, EHR integration, mobile app, SMS gateway.

## Expected outcomes
Working cloud-hosted system, measured test evidence, architecture documentation, reproducible deployment.
