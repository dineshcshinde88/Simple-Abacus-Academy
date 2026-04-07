CREATE DATABASE IF NOT EXISTS abacus_worksheets;
USE abacus_worksheets;

CREATE TABLE IF NOT EXISTS worksheets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_name VARCHAR(100) NOT NULL,
  operation VARCHAR(20) NOT NULL,
  digits INT NOT NULL,
  total_questions INT NOT NULL,
  rows_count INT NOT NULL,
  level INT DEFAULT NULL,
  data JSON NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
