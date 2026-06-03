-- Simple Abacus Learning Website Admin DB Schema
-- Create database first, then run this script.

CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  profile_image VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  phone_country VARCHAR(10) NOT NULL DEFAULT '+91',
  phone VARCHAR(20) NOT NULL,
  course VARCHAR(120) NOT NULL,
  gender VARCHAR(20) NOT NULL DEFAULT '',
  mother_tongue VARCHAR(120) NOT NULL DEFAULT '',
  dob DATE NULL,
  password VARCHAR(255) NOT NULL DEFAULT '',
  status ENUM('active','inactive') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS subscriptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  plan_name VARCHAR(120) NOT NULL,
  amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  payment_status ENUM('paid','unpaid') DEFAULT 'unpaid',
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS demo_bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  preferred_date DATE NOT NULL,
  message TEXT,
  status ENUM('pending','completed') DEFAULT 'pending'
);

CREATE TABLE IF NOT EXISTS blog_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_name VARCHAR(160) NOT NULL,
  slug VARCHAR(180) NOT NULL UNIQUE,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS blogs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NULL,
  title VARCHAR(220) NOT NULL,
  slug VARCHAR(240) NOT NULL UNIQUE,
  author VARCHAR(140) NOT NULL DEFAULT 'Admin',
  publish_date DATE NULL,
  excerpt TEXT NULL,
  description MEDIUMTEXT NULL,
  featured_image VARCHAR(255) NULL,
  meta_title VARCHAR(220) NULL,
  meta_description TEXT NULL,
  meta_keywords VARCHAR(255) NULL,
  status ENUM('draft','published') NOT NULL DEFAULT 'draft',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_blogs_category (category_id),
  INDEX idx_blogs_status (status),
  CONSTRAINT fk_blogs_category FOREIGN KEY (category_id) REFERENCES blog_categories(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS teachers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  phone VARCHAR(20) NOT NULL,
  expertise VARCHAR(160) NOT NULL,
  qualification VARCHAR(160) NOT NULL DEFAULT 'Certified Abacus Trainer',
  experience VARCHAR(120) NOT NULL DEFAULT '',
  location VARCHAR(160) NOT NULL DEFAULT '',
  specialization VARCHAR(120) NOT NULL DEFAULT 'Abacus',
  image VARCHAR(255) NOT NULL DEFAULT '',
  description TEXT NULL,
  joining_date DATE NOT NULL,
  status ENUM('active','inactive') DEFAULT 'active'
);

INSERT INTO teachers
  (name, email, phone, expertise, qualification, experience, location, specialization, image, description, joining_date, status)
VALUES
  ('Poonam Yuvraj Gavhane', 'poonam.gavhane@simpleabacus.com', 'Not added', 'Abacus', 'Certified Abacus Trainer', '5+ Years Experience', 'Pune, Maharashtra', 'Abacus', '/assets/teachers/poonam-gavhane.png', 'Patient, structured instruction that builds number sense, focus, and confident mental math habits.', '2026-01-01', 'active'),
  ('Mahanthi Kamini Devi', 'mahanthi.kamini.devi@simpleabacus.com', 'Not added', 'Abacus', 'Certified Abacus Trainer', '6+ Years Experience', 'Thane, Maharashtra', 'Abacus', '/assets/teachers/mahanthi-kamini-devi.png', 'Known for engaging classes and step-by-step guidance that keeps learners motivated and consistent.', '2026-01-01', 'active'),
  ('Nayana Uday Patil', 'nayana.uday.patil@simpleabacus.com', 'Not added', 'Abacus', 'Certified Abacus Trainer', '4+ Years Experience', 'Pune, Maharashtra', 'Abacus', '/assets/teachers/nayana-uday-patil.png', 'Focuses on accuracy, speed, and confidence with child-friendly teaching and regular feedback.', '2026-01-01', 'active'),
  ('Ashvini Balu Talekar', 'ashvini.balu.talekar@simpleabacus.com', 'Not added', 'Abacus', 'Certified Abacus Trainer', '5+ Years Experience', 'Pune, Maharashtra', 'Abacus', '/assets/teachers/ashvini-balu-talekar.png', 'Encouraging mentor who blends fun practice with clear fundamentals and personalized attention.', '2026-01-01', 'active')
ON DUPLICATE KEY UPDATE email = email;

-- Sample admin user (password: Admin@123)
-- Replace email/name and change password after first login.
INSERT INTO admins (name, email, password)
VALUES ('Admin', 'admin@simpleabacus.com', '$2y$10$0FRd5zBbHWNSK/VBBesajOWgJ.M0awaN0oSrNGQaRN.aGHqfig16W')
ON DUPLICATE KEY UPDATE name = VALUES(name), password = VALUES(password);
