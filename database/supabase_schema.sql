-- =====================================================================
-- CityCare Hospital - Supabase / PostgreSQL schema
-- SAFE TO RE-RUN: creates what is missing, upgrades old installs, never drops data.
-- Paste into Supabase -> SQL Editor -> Run.  (The app also runs this automatically on start
-- when AUTO_MIGRATE=1, see database/init.php.)
-- =====================================================================

-- 1. Users
CREATE TABLE IF NOT EXISTS users (
  id SERIAL PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  phone VARCHAR(20),
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'patient' CHECK (role IN ('patient','admin')),
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- 2. Departments
CREATE TABLE IF NOT EXISTS departments (
  id SERIAL PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE
);

-- 3. Doctors   (is_active is BOOLEAN: the PHP code uses true/false)
CREATE TABLE IF NOT EXISTS doctors (
  id SERIAL PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  department_id INT NOT NULL REFERENCES departments(id) ON DELETE RESTRICT,
  qualification VARCHAR(150),
  fee NUMERIC(8,2) DEFAULT 0,
  is_active BOOLEAN NOT NULL DEFAULT TRUE
);

-- 4. Slots   (is_booked is BOOLEAN)
CREATE TABLE IF NOT EXISTS slots (
  id SERIAL PRIMARY KEY,
  doctor_id INT NOT NULL REFERENCES doctors(id) ON DELETE CASCADE,
  slot_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  is_booked BOOLEAN NOT NULL DEFAULT FALSE,
  CONSTRAINT uq_slot UNIQUE (doctor_id, slot_date, start_time)
);

-- 5. Appointments   (reminder_sent is BOOLEAN; active_slot_id UNIQUE = DB-level double-booking guard)
CREATE TABLE IF NOT EXISTS appointments (
  id SERIAL PRIMARY KEY,
  user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  slot_id INT NOT NULL REFERENCES slots(id) ON DELETE CASCADE,
  active_slot_id INT NULL UNIQUE,
  reason VARCHAR(255),
  status VARCHAR(20) NOT NULL DEFAULT 'booked' CHECK (status IN ('booked','cancelled','completed')),
  report_key VARCHAR(255) NULL,
  reminder_sent BOOLEAN NOT NULL DEFAULT FALSE,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- 6. UPGRADE old installs: the first version of this script used SMALLINT for the three flags, which makes
--    every query like  WHERE is_active = true  fail with "operator does not exist: smallint = boolean".
DO $$
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema()
             AND table_name = 'doctors' AND column_name = 'is_active' AND data_type = 'smallint') THEN
    ALTER TABLE doctors ALTER COLUMN is_active DROP DEFAULT;
    ALTER TABLE doctors ALTER COLUMN is_active TYPE BOOLEAN USING (COALESCE(is_active, 1) <> 0);
    ALTER TABLE doctors ALTER COLUMN is_active SET DEFAULT TRUE;
    ALTER TABLE doctors ALTER COLUMN is_active SET NOT NULL;
  END IF;
  IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema()
             AND table_name = 'slots' AND column_name = 'is_booked' AND data_type = 'smallint') THEN
    ALTER TABLE slots ALTER COLUMN is_booked DROP DEFAULT;
    ALTER TABLE slots ALTER COLUMN is_booked TYPE BOOLEAN USING (COALESCE(is_booked, 0) <> 0);
    ALTER TABLE slots ALTER COLUMN is_booked SET DEFAULT FALSE;
    ALTER TABLE slots ALTER COLUMN is_booked SET NOT NULL;
  END IF;
  IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema()
             AND table_name = 'appointments' AND column_name = 'reminder_sent' AND data_type = 'smallint') THEN
    ALTER TABLE appointments ALTER COLUMN reminder_sent DROP DEFAULT;
    ALTER TABLE appointments ALTER COLUMN reminder_sent TYPE BOOLEAN USING (COALESCE(reminder_sent, 0) <> 0);
    ALTER TABLE appointments ALTER COLUMN reminder_sent SET DEFAULT FALSE;
    ALTER TABLE appointments ALTER COLUMN reminder_sent SET NOT NULL;
  END IF;
END $$;

-- 7. Indexes
CREATE INDEX IF NOT EXISTS idx_slots_lookup ON slots (doctor_id, slot_date, is_booked);
CREATE INDEX IF NOT EXISTS idx_appointments_user ON appointments (user_id);
CREATE INDEX IF NOT EXISTS idx_appointments_status ON appointments (status);
CREATE INDEX IF NOT EXISTS idx_appointments_slot ON appointments (slot_id);

-- 8. SECURITY: Supabase exposes every table in "public" through its REST API using the public anon key.
--    This app talks to Postgres directly (which bypasses RLS), so turn RLS on with NO policies:
--    that blocks the REST API completely and keeps users.password_hash / patient data private.
ALTER TABLE users        ENABLE ROW LEVEL SECURITY;
ALTER TABLE departments  ENABLE ROW LEVEL SECURITY;
ALTER TABLE doctors      ENABLE ROW LEVEL SECURITY;
ALTER TABLE slots        ENABLE ROW LEVEL SECURITY;
ALTER TABLE appointments ENABLE ROW LEVEL SECURITY;

-- 9. Seed data (idempotent: departments by unique name, doctors only if the name is not present yet)
INSERT INTO departments (name) VALUES
  ('General Medicine'), ('Cardiology'), ('Orthopedics'), ('Pediatrics'), ('Dermatology')
ON CONFLICT (name) DO NOTHING;

INSERT INTO doctors (name, department_id, qualification, fee)
SELECT v.name, d.id, v.qualification, v.fee
FROM (VALUES
  ('Dr. Anitha Rao',          'General Medicine', 'MBBS, MD',              300),
  ('Dr. Karthik Subramanian', 'Cardiology',       'MBBS, DM Cardiology',   700),
  ('Dr. Meena Iyer',          'Orthopedics',      'MBBS, MS Ortho',        600),
  ('Dr. Suresh Kumar',        'Pediatrics',       'MBBS, DCH',             400),
  ('Dr. Divya Nair',          'Dermatology',      'MBBS, MD Dermatology',  500)
) AS v(name, dept, qualification, fee)
JOIN departments d ON d.name = v.dept
WHERE NOT EXISTS (SELECT 1 FROM doctors x WHERE x.name = v.name);
