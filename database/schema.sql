CREATE DATABASE IF NOT EXISTS hospital_db CHARACTER SET utf8mb4;
USE hospital_db;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  phone VARCHAR(20),
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('patient','admin') NOT NULL DEFAULT 'patient',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE departments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE doctors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  department_id INT NOT NULL,
  qualification VARCHAR(150),
  fee DECIMAL(8,2) DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  FOREIGN KEY (department_id) REFERENCES departments(id)
) ENGINE=InnoDB;

CREATE TABLE slots (
  id INT AUTO_INCREMENT PRIMARY KEY,
  doctor_id INT NOT NULL,
  slot_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  is_booked TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE KEY uq_slot (doctor_id, slot_date, start_time),
  KEY idx_lookup (doctor_id, slot_date, is_booked),
  FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE appointments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  slot_id INT NOT NULL,
  active_slot_id INT NULL UNIQUE,   -- = slot_id while 'booked'; NULL otherwise. DB-level double-booking guard.
  reason VARCHAR(255),
  status ENUM('booked','cancelled','completed') NOT NULL DEFAULT 'booked',
  report_key VARCHAR(255) NULL,
  reminder_sent TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_user (user_id),
  KEY idx_status (status),
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (slot_id) REFERENCES slots(id)
) ENGINE=InnoDB;
