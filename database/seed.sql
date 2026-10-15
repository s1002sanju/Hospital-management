USE hospital_db;
INSERT IGNORE INTO departments (name) VALUES ('General Medicine'),('Cardiology'),('Orthopedics'),('Pediatrics'),('Dermatology');
INSERT INTO doctors (name, department_id, qualification, fee) VALUES
('Dr. Anitha Rao', 1, 'MBBS, MD', 300),
('Dr. Karthik Subramanian', 2, 'MBBS, DM Cardiology', 700),
('Dr. Meena Iyer', 3, 'MBBS, MS Ortho', 600),
('Dr. Suresh Kumar', 4, 'MBBS, DCH', 400),
('Dr. Divya Nair', 5, 'MBBS, MD Dermatology', 500);
