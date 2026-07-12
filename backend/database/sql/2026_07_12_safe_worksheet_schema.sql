-- Safe worksheet schema migration for production.
-- Purpose: create/extend only worksheet content tables used by the current backend.
-- Safe rules: no DROP, no TRUNCATE, no RENAME, no changes to users/students/payments/orders/subscriptions/Razorpay data.
-- Run this before 2026_07_12_import_foundation_level1_worksheet_content.sql.

CREATE TABLE IF NOT EXISTS worksheet_levels (
  id VARCHAR(191) NOT NULL PRIMARY KEY,
  level_name VARCHAR(191) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS worksheet_topics (
  id VARCHAR(191) NOT NULL PRIMARY KEY,
  level_id VARCHAR(191) NOT NULL,
  topic_name VARCHAR(255) NOT NULL,
  total_questions INT NOT NULL DEFAULT 0,
  INDEX idx_worksheet_topics_level_id (level_id),
  CONSTRAINT fk_worksheet_topics_level
    FOREIGN KEY (level_id) REFERENCES worksheet_levels(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS worksheet_questions (
  id VARCHAR(191) NOT NULL PRIMARY KEY,
  topic_id VARCHAR(191) NOT NULL,
  paper_id VARCHAR(191) NULL,
  question_number INT NULL,
  question VARCHAR(255) NOT NULL,
  question_rows LONGTEXT NULL,
  answer VARCHAR(100) NOT NULL,
  source_hash VARCHAR(64) NULL,
  import_batch VARCHAR(191) NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  INDEX idx_worksheet_questions_topic_id (topic_id),
  INDEX idx_worksheet_questions_paper_id (paper_id),
  CONSTRAINT fk_worksheet_questions_topic
    FOREIGN KEY (topic_id) REFERENCES worksheet_topics(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS worksheet_papers (
  id VARCHAR(191) NOT NULL PRIMARY KEY,
  level_id VARCHAR(191) NOT NULL,
  topic_id VARCHAR(191) NULL,
  paper_number INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  total_questions INT NOT NULL DEFAULT 0,
  source_file VARCHAR(255) NULL,
  source_hash VARCHAR(64) NULL,
  import_batch VARCHAR(191) NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY uniq_worksheet_papers_level_paper (level_id, paper_number),
  INDEX idx_worksheet_papers_level_id (level_id),
  INDEX idx_worksheet_papers_topic_id (topic_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS question_options (
  id VARCHAR(191) NOT NULL PRIMARY KEY,
  question_id VARCHAR(191) NOT NULL,
  option_text VARCHAR(255) NOT NULL,
  is_correct TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  INDEX idx_question_options_question_id (question_id),
  CONSTRAINT fk_question_options_question
    FOREIGN KEY (question_id) REFERENCES worksheet_questions(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS worksheet_practices (
  id VARCHAR(191) NOT NULL PRIMARY KEY,
  student_id VARCHAR(191) NOT NULL,
  topic_id VARCHAR(191) NOT NULL,
  score INT NOT NULL DEFAULT 0,
  accuracy DECIMAL(5,2) NOT NULL DEFAULT 0,
  total_questions INT NOT NULL DEFAULT 0,
  correct_answers INT NOT NULL DEFAULT 0,
  time_taken INT NOT NULL DEFAULT 0,
  status VARCHAR(40) NOT NULL DEFAULT 'Needs Practice',
  mode VARCHAR(30) NOT NULL DEFAULT 'practice',
  speed_tier INT NULL,
  created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  INDEX idx_worksheet_practices_student_id (student_id),
  INDEX idx_worksheet_practices_topic_id (topic_id),
  CONSTRAINT fk_worksheet_practices_topic
    FOREIGN KEY (topic_id) REFERENCES worksheet_topics(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS worksheet_competition_unlocks (
  id VARCHAR(191) NOT NULL PRIMARY KEY,
  student_id VARCHAR(191) NOT NULL,
  topic_id VARCHAR(191) NOT NULL,
  unlocked_tier INT NOT NULL DEFAULT 15,
  passing_percentage DECIMAL(5,2) NOT NULL DEFAULT 90,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uniq_worksheet_competition_topic (student_id, topic_id),
  INDEX idx_worksheet_competition_student (student_id),
  INDEX idx_worksheet_competition_topic (topic_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS worksheet_competition_config (
  id TINYINT PRIMARY KEY,
  passing_percentage DECIMAL(5,2) NOT NULL DEFAULT 90,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO worksheet_competition_config (id, passing_percentage, updated_at)
VALUES (1, 90, NOW())
ON DUPLICATE KEY UPDATE passing_percentage = passing_percentage;

CREATE TABLE IF NOT EXISTS worksheet_import_runs (
  id VARCHAR(191) NOT NULL PRIMARY KEY,
  source_hash VARCHAR(64) NOT NULL,
  source_file VARCHAR(255) NULL,
  level_number VARCHAR(20) NULL,
  paper_count INT NOT NULL DEFAULT 0,
  question_count INT NOT NULL DEFAULT 0,
  force_import TINYINT(1) NOT NULL DEFAULT 0,
  status VARCHAR(30) NOT NULL,
  message TEXT NULL,
  imported_at DATETIME NOT NULL,
  UNIQUE KEY uniq_worksheet_import_source_hash (source_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELIMITER $$
CREATE PROCEDURE safe_add_worksheet_column(IN p_table VARCHAR(64), IN p_column VARCHAR(64), IN p_definition TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_column
  ) THEN
    SET @sql = CONCAT('ALTER TABLE `', REPLACE(p_table, '`', '``'), '` ADD COLUMN `', REPLACE(p_column, '`', '``'), '` ', p_definition);
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END$$
DELIMITER ;

CALL safe_add_worksheet_column('worksheet_questions', 'paper_id', 'VARCHAR(191) NULL AFTER topic_id');
CALL safe_add_worksheet_column('worksheet_questions', 'question_number', 'INT NULL AFTER paper_id');
CALL safe_add_worksheet_column('worksheet_questions', 'question_rows', 'LONGTEXT NULL AFTER question');
CALL safe_add_worksheet_column('worksheet_questions', 'source_hash', 'VARCHAR(64) NULL AFTER answer');
CALL safe_add_worksheet_column('worksheet_questions', 'import_batch', 'VARCHAR(191) NULL AFTER source_hash');
CALL safe_add_worksheet_column('worksheet_questions', 'created_at', 'DATETIME NULL AFTER import_batch');
CALL safe_add_worksheet_column('worksheet_questions', 'updated_at', 'DATETIME NULL AFTER created_at');
CALL safe_add_worksheet_column('worksheet_practices', 'mode', 'VARCHAR(30) NOT NULL DEFAULT "practice" AFTER status');
CALL safe_add_worksheet_column('worksheet_practices', 'speed_tier', 'INT NULL AFTER mode');

DROP PROCEDURE safe_add_worksheet_column;
