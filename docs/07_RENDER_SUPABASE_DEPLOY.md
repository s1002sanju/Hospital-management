# Deploying on Render + Supabase

Render runs the PHP app (Docker). Supabase provides the PostgreSQL database and the private storage bucket for medical reports.
Everything below is set through **environment variables** - nothing secret lives in the code.

## 0. First, rotate the leaked password
An earlier version of `config/config.php` contained the real Supabase database password. It is gone from the file, but it is
still in the git history. **Supabase -> Project Settings -> Database -> Reset database password**, and use the new one below.
(If the GitHub repo is public, treat the old password as compromised.)

## 1. Supabase
1. **Database connection values**: click **Connect** (top bar) -> *Session pooler*. Copy host, port, user.
   - Host looks like `aws-1-ap-south-1.pooler.supabase.com` - copy it from the dialog, do not guess (`aws-0` vs `aws-1` differs per project).
   - User is `postgres.<project-ref>` (with the dot and ref), **not** just `postgres`.
   - Do **not** use the "Direct connection" host `db.<ref>.supabase.co`: it is IPv6-only and Render cannot reach it.
2. **Storage values**: Project Settings -> API -> copy the **Project URL** and the **service_role** key (server-side secret; never put it in browser code).
3. Tables and the storage bucket are created **automatically** on the first start (see `database/init.php`).
   If you prefer to do it by hand: SQL Editor -> paste `database/supabase_schema.sql` -> Run. The script is safe to re-run and also
   upgrades the old version of the tables (the one that caused `operator does not exist: smallint = boolean`).

## 2. Render
1. Push this repo to GitHub.
2. Render -> **New -> Blueprint** -> pick the repo (reads `render.yaml`).  *(Or New -> Web Service -> Docker and add the variables by hand.)*
3. Fill in the variables Render asks for:

| Variable | Value |
|---|---|
| `DB_HOST` | pooler host from step 1 |
| `DB_PORT` | `5432` (session pooler). `6543` also works |
| `DB_USER` | `postgres.<project-ref>` |
| `DB_PASS` | the (new) database password - special characters are fine, no URL-encoding |
| `SUPABASE_URL` | `https://<project-ref>.supabase.co` |
| `SUPABASE_SERVICE_ROLE_KEY` | service_role key |
| `ADMIN_EMAIL` / `ADMIN_PASSWORD` | your first admin login (8+ chars). Created on first start; remove `ADMIN_PASSWORD` afterwards if you like |
| `RESEND_API_KEY` / `MAIL_FROM` | optional - real confirmation/reminder emails (see below) |

   Already set automatically: `DB_NAME=postgres`, `DB_SSLMODE=require`, `FORCE_HTTPS=1`, `APP_TIMEZONE=Asia/Kolkata`, `AUTO_MIGRATE=1`, `REMINDER_KEY` (random).
4. Deploy. In **Logs** you should see:
   ```
   [init] database connection OK (...)
   [init] schema is up to date
   [init] admin you@example.com created
   [init] storage bucket "medical-reports" created (private)
   ```
5. Open the URL -> log in as the admin -> **Slots** -> generate slots -> register a patient -> book.

## 3. Optional: emails + daily reminders
* Render's free plan **blocks SMTP**, so email goes through the Resend HTTPS API: create an API key at resend.com, set `RESEND_API_KEY` and
  `MAIL_FROM` (a sender on a domain you verified in Resend). Without these, emails are only written to the log and nothing breaks.
* Reminders: add two GitHub repo secrets - `APP_URL` (your Render URL) and `REMINDER_KEY` (copy the value from Render's environment).
  `.github/workflows/reminders.yml` then calls `/api/reminders.php?action=send` every morning at 08:00 IST. A reminder is only marked as sent
  when the email provider accepted it.

## 4. Troubleshooting (check Render -> Logs first)
| What you see | Cause / fix |
|---|---|
| `Network is unreachable` / connection timeout | Using the direct `db.<ref>.supabase.co` host. Use the **Session pooler** host. |
| `FATAL: Tenant or user not found` | `DB_USER` must be `postgres.<project-ref>`, and `DB_HOST` must be the exact pooler host shown in the Connect dialog. |
| `password authentication failed` | Wrong/old `DB_PASS`. Reset it in Supabase and update Render. |
| `Database is not configured` | `DB_HOST` / `DB_USER` / `DB_PASS` not set in Render. |
| `operator does not exist: smallint = boolean` | Old table definition. Redeploy (auto-fixes) or run `database/supabase_schema.sql`. |
| Blank home page | An empty `public/index.html` used to hide `index.php`. Removed. |
| "Report storage is unavailable" | Check the `[init]`/`Supabase upload failed` log lines: wrong URL or service key. |
| First page load takes ~30-60 s | Free plan sleeps after 15 min idle. |
| Logged out after the site was idle | Free plan restarts lose PHP sessions (they live on the container's disk). Normal on the free tier. |

## 5. Local development
```bash
# local Postgres, or point DB_* at Supabase
export DB_HOST=127.0.0.1 DB_USER=postgres DB_PASS=secret DB_NAME=hospital DB_SSLMODE=disable
export ADMIN_EMAIL=admin@example.com ADMIN_PASSWORD='Admin@12345' REMINDER_KEY=local-key
php database/init.php          # creates tables + admin
php -S localhost:8000 -t public
BASE_URL=http://localhost:8000 python tests/integration_test.py   # uses ADMIN_* and REMINDER_KEY from the environment
```
