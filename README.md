# CityCare - Hospital Appointment Booking System
Course: Cloud Application Development (24CAC631) - Student: Sanjana S (25MCA047)

**Stack:** PHP 8.2 (Apache, Docker) - **Render** (hosting) - **Supabase** (PostgreSQL + private Storage) - Bootstrap 5 - GitHub Actions

## Deploy
See **[docs/07_RENDER_SUPABASE_DEPLOY.md](docs/07_RENDER_SUPABASE_DEPLOY.md)** (env vars, first admin, troubleshooting).
The app creates its own tables, first admin account and storage bucket on start-up (`database/init.php`).

## Folders
| Path | Purpose |
|---|---|
| `public/` | Web root: patient pages, `admin/`, `api/`, `health.php` |
| `includes/` | Sessions/CSRF, DB connection, email + Supabase Storage helpers |
| `config/config.php` | Reads every setting from environment variables (no secrets in code) |
| `database/` | `supabase_schema.sql` (idempotent), `init.php` (start-up tasks), MySQL scripts for local use |
| `docker/`, `Dockerfile`, `render.yaml` | Container + Render Blueprint |
| `.github/workflows/` | `ci.yml` (PHP lint), `reminders.yml` (daily reminder call) |
| `tests/` | Integration, double-booking race and k6 load tests |
| `legacy-aws/` | Original Elastic Beanstalk config and Lambda reminder (kept for the AWS coursework docs, not used on Render) |
| `docs/` | Project documents; `07_...` is the current Render + Supabase guide, `05_AWS_SETUP.md` the original AWS design |
