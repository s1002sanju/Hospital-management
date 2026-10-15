# Literature Survey (template)

## A. Existing-systems comparison (fill/verify prices and features from each vendor's website)
| Feature | Practo / hospital portals | Calendly / Acuity / Setmore | Manual / phone booking | **Your system** |
|---|---|---|---|---|
| Live slot availability | Yes | Yes | No | Yes |
| Double-booking prevention proven under concurrency | Not documented | Not documented | No | **Yes – tested with 50 simultaneous users** |
| Department/doctor model for a hospital | Yes | No (generic calendar) | – | Yes |
| Medical report upload (encrypted) | Partial | No | No | Yes (S3 SSE) |
| Automated reminders | Yes | Yes | No | Yes (serverless) |
| Cost for a small hospital | Commission / subscription | Subscription per user | Staff time | Pay-per-use AWS (free tier friendly) |
| Open / customisable | No | No | – | Yes |
| Auto-scaling, Multi-AZ, CI/CD | Hidden | Hidden | No | Yes, demonstrable |

## B. Research papers – how to find 8–10 genuine references
Search **IEEE Xplore, ScienceDirect, Springer, Google Scholar** (2018–2026) with:
- "online hospital appointment scheduling system"
- "cloud-based appointment booking web application"
- "serverless architecture web application scalability"
- "concurrency control double booking database"
- "healthcare cloud security privacy patient data"
- "auto scaling performance evaluation AWS"

For each paper record: Authors · Year · Title · Venue · What they built · Limitation. Cite in IEEE style. **Do not cite anything you haven't opened** – you will be asked about it in the viva.

| # | Reference | Technique used | Limitation / gap |
|---|---|---|---|
| 1 | | | |

## C. Research gap statement (adapt)
"Existing appointment systems are either generic calendar tools lacking hospital workflows, or commercial platforms that are costly and closed. Few small-hospital solutions demonstrate measured concurrency safety, cloud-native scalability and event-driven automation together. This project proposes a low-cost, secure, auto-scaling AWS-based system with evidence from load and race testing."
