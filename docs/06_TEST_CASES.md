# Testing & Validation (fill the Result/Actual columns with real outcomes)

## A. Functional test cases
| ID | Module | Test | Input | Expected | Actual | Pass/Fail |
|---|---|---|---|---|---|---|
| F1 | Register | Valid registration | valid data | Account created | | |
| F2 | Register | Duplicate email | existing email | Error shown | | |
| F3 | Register | Weak password | "abc" | Rejected | | |
| F4 | Login | Valid login | correct creds | Redirect to /book.php | | |
| F5 | Login | Wrong password | wrong | Error | | |
| F6 | Booking | Book free slot | choose slot | Confirmation + email | | |
| F7 | Booking | Book taken slot | stale page | "slot was just taken" | | |
| F8 | Booking | Past slot | past date | Not listed / rejected | | |
| F9 | Cancel | Cancel booked | click Cancel | Status cancelled, slot free again | | |
| F10 | Report | Upload PDF ≤2 MB | file | Stored in S3 | | |
| F11 | Report | Upload .exe | file | Rejected | | |
| F12 | Admin | Create slots | doctor + dates | Slots created, no duplicates | | |
| F13 | Admin | Mark completed | click | Status updated | | |
| F14 | Admin | Dashboard | open | Counts and charts match DB | | |

## B. Integration tests
| ID | Test | Expected | Actual |
|---|---|---|---|
| I1 | App ↔ RDS | Booking row visible in DB | |
| I2 | App ↔ S3 | Object present, encrypted | |
| I3 | App ↔ SES | Email received | |
| I4 | App ↔ SNS | Admin email received | |
| I5 | EventBridge → Lambda → app → SES | Reminder email, `reminder_sent=1` | |
| I6 | GitHub push → Beanstalk | New version live | |

## C. Concurrency test (`tests/race_test.py`)
| Users | Same slot | Successful bookings | Pass (=1)? |
|---|---|---|---|
| 10 | | | |
| 50 | | | |
| 100 | | | |

## D. Load / performance (`tests/load_test.js`, k6)
| Concurrent users | Avg resp (ms) | p95 (ms) | Requests/s | Error % | Instances | CPU % |
|---|---|---|---|---|---|---|
| 10 | | | | | | |
| 50 | | | | | | |
| 100 | | | | | | |
| 500 | | | | | | |

**Analysis (write 5–6 lines):** At what load did latency rise? When did Auto Scaling trigger and how long did it take? Where was the bottleneck (CPU, DB connections)? What optimisation would help (caching, read replica, larger instance, connection pooling)?

## E. Security tests
See `tests/security_checks.md`.

## F. Availability test (optional, high value)
Enable RDS Multi-AZ → Reboot with failover → measure downtime → note it. Terminate one Beanstalk instance → ALB keeps serving, Auto Scaling replaces it.
