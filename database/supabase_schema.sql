-- Supabase / PostgreSQL Schema & Initial Data for CityCare Hospital
-- Copy and paste this script directly into the Supabase SQL Editor and click Run.

-- 1. Users table
CREATE TABLE IF NOT EXISTS users (
  id SERIAL PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  phone VARCHAR(20),
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'patient' CHECK (role IN ('patient','admin')),
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- 2. Departments table
CREATE TABLE IF NOT EXISTS departments (
  id SERIAL PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE
);

-- 3. Doctors table
CREATE TABLE IF NOT EXISTS doctors (
  id SERIAL PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  department_id INT NOT NULL REFERENCES departments(id) ON DELETE RESTRICT,
  qualification VARCHAR(150),
  fee NUMERIC(8,2) DEFAULT 0,
  is_active SMALLINT DEFAULT 1
);

-- 4. Slots table
CREATE TABLE IF NOT EXISTS slots (
  id SERIAL PRIMARY KEY,
  doctor_id INT NOT NULL REFERENCES doctors(id) ON DELETE CASCADE,
  slot_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  is_booked SMALLINT NOT NULL DEFAULT 0,
  CONSTRAINT uq_slot UNIQUE (doctor_id, slot_date, start_time)
);
CREATE INDEX IF NOT EXISTS idx_slots_lookup ON slots (doctor_id, slot_date, is_booked);

-- 5. Appointments table
CREATE TABLE IF NOT EXISTS appointments (
  id SERIAL PRIMARY KEY,
  user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  slot_id INT NOT NULL REFERENCES slots(id) ON DELETE CASCADE,
  active_slot_id INT NULL UNIQUE,   -- DB-level double-booking guard (active slot ID when booked)
  reason VARCHAR(255),
  status VARCHAR(20) NOT NULL DEFAULT 'booked' CHECK (status IN ('booked','cancelled','completed')),
  report_key VARCHAR(255) NULL,
  reminder_sent SMALLINT DEFAULT 0,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_appointments_user ON appointments (user_id);
CREATE INDEX IF NOT EXISTS idx_appointments_status ON appointments (status);

-- 6. Initial Departments
INSERT INTO departments (name) VALUES 
  ('General Medicine'),
  ('Cardiology'),
  ('Orthopedics'),
  ('Pediatrics'),
  ('Dermatology')
ON CONFLICT (name) DO NOTHING;

-- 7. Initial Doctors
INSERT INTO doctors (name, department_id, qualification, fee) VALUES
  ('Dr. Anitha Rao', 1, 'MBBS, MD', 300),
  ('Dr. Karthik Subramanian', 2, 'MBBS, DM Cardiology', 700),
  ('Dr. Meena Iyer', 3, 'MBBS, MS Ortho', 600),
  ('Dr. Suresh Kumar', 4, 'MBBS, DCH', 400),
  ('Dr. Divya Nair', 5, 'MBBS, MD Dermatology', 500)
ON CONFLICT DO NOTHING;

