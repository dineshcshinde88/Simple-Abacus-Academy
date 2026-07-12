-- Production-safe worksheet schema compatibility migration.
-- Purpose: update old production worksheet tables to match the current localhost worksheet schema.
-- Safe scope: worksheet tables only.
-- No DROP TABLE, no TRUNCATE, no table rename, no writes to users/students/payments/orders/subscriptions/Razorpay/admin data.
-- Run before importing Level 0 / Level 1 worksheet paper/question data.

SET @db_name := DATABASE();

CREATE TABLE IF NOT EXISTS worksheet_levels (
  id VARCHAR(191) NOT NULL PRIMARY KEY,
  level_name VARCHAR(191) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS worksheet_topics (
  id VARCHAR(191) NOT NULL PRIMARY KEY,
  level_id VARCHAR(191) NOT NULL,
  topic_name VARCHAR(255) NOT NULL,
  total_questions INT NOT NULL DEFAULT 0,
  INDEX idx_worksheet_topics_level_id (level_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS worksheet_questions (
  id VARCHAR(191) NOT NULL PRIMARY KEY,
  topic_id VARCHAR(191) NULL,
  paper_id VARCHAR(191) NULL,
  question_number INT NULL,
  question VARCHAR(255) NOT NULL,
  question_rows LONGTEXT NULL,
  answer VARCHAR(100) NOT NULL,
  source_hash VARCHAR(64) NULL,
  import_batch VARCHAR(191) NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL
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
  imported_at DATETIME NULL,
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
  INDEX idx_question_options_question_id (question_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add missing columns to existing production worksheet_questions.
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'worksheet_questions' AND COLUMN_NAME = 'topic_id') = 0,
  'ALTER TABLE worksheet_questions ADD COLUMN topic_id VARCHAR(191) NULL AFTER id',
  'SELECT "worksheet_questions.topic_id already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'worksheet_questions' AND COLUMN_NAME = 'paper_id') = 0,
  'ALTER TABLE worksheet_questions ADD COLUMN paper_id VARCHAR(191) NULL AFTER topic_id',
  'SELECT "worksheet_questions.paper_id already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'worksheet_questions' AND COLUMN_NAME = 'question_number') = 0,
  'ALTER TABLE worksheet_questions ADD COLUMN question_number INT NULL AFTER paper_id',
  'SELECT "worksheet_questions.question_number already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'worksheet_questions' AND COLUMN_NAME = 'question_rows') = 0,
  'ALTER TABLE worksheet_questions ADD COLUMN question_rows LONGTEXT NULL AFTER question',
  'SELECT "worksheet_questions.question_rows already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'worksheet_questions' AND COLUMN_NAME = 'source_hash') = 0,
  'ALTER TABLE worksheet_questions ADD COLUMN source_hash VARCHAR(64) NULL AFTER answer',
  'SELECT "worksheet_questions.source_hash already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'worksheet_questions' AND COLUMN_NAME = 'import_batch') = 0,
  'ALTER TABLE worksheet_questions ADD COLUMN import_batch VARCHAR(191) NULL AFTER source_hash',
  'SELECT "worksheet_questions.import_batch already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'worksheet_questions' AND COLUMN_NAME = 'created_at') = 0,
  'ALTER TABLE worksheet_questions ADD COLUMN created_at DATETIME NULL AFTER import_batch',
  'SELECT "worksheet_questions.created_at already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'worksheet_questions' AND COLUMN_NAME = 'updated_at') = 0,
  'ALTER TABLE worksheet_questions ADD COLUMN updated_at DATETIME NULL AFTER created_at',
  'SELECT "worksheet_questions.updated_at already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Ensure indexes required by current backend. These are no-op if already present.
SET @sql := IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'worksheet_questions' AND INDEX_NAME = 'idx_worksheet_questions_topic_id') = 0,
  'CREATE INDEX idx_worksheet_questions_topic_id ON worksheet_questions (topic_id)',
  'SELECT "idx_worksheet_questions_topic_id already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'worksheet_questions' AND INDEX_NAME = 'idx_worksheet_questions_paper_id') = 0,
  'CREATE INDEX idx_worksheet_questions_paper_id ON worksheet_questions (paper_id)',
  'SELECT "idx_worksheet_questions_paper_id already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add missing columns to worksheet_papers if table already existed with older shape.
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'worksheet_papers' AND COLUMN_NAME = 'topic_id') = 0,
  'ALTER TABLE worksheet_papers ADD COLUMN topic_id VARCHAR(191) NULL AFTER level_id',
  'SELECT "worksheet_papers.topic_id already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'worksheet_papers' AND COLUMN_NAME = 'source_file') = 0,
  'ALTER TABLE worksheet_papers ADD COLUMN source_file VARCHAR(255) NULL AFTER total_questions',
  'SELECT "worksheet_papers.source_file already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'worksheet_papers' AND COLUMN_NAME = 'source_hash') = 0,
  'ALTER TABLE worksheet_papers ADD COLUMN source_hash VARCHAR(64) NULL AFTER source_file',
  'SELECT "worksheet_papers.source_hash already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'worksheet_papers' AND COLUMN_NAME = 'import_batch') = 0,
  'ALTER TABLE worksheet_papers ADD COLUMN import_batch VARCHAR(191) NULL AFTER source_hash',
  'SELECT "worksheet_papers.import_batch already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'worksheet_papers' AND COLUMN_NAME = 'imported_at') = 0,
  'ALTER TABLE worksheet_papers ADD COLUMN imported_at DATETIME NULL AFTER import_batch',
  'SELECT "worksheet_papers.imported_at already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'worksheet_papers' AND COLUMN_NAME = 'created_at') = 0,
  'ALTER TABLE worksheet_papers ADD COLUMN created_at DATETIME NULL AFTER imported_at',
  'SELECT "worksheet_papers.created_at already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'worksheet_papers' AND COLUMN_NAME = 'updated_at') = 0,
  'ALTER TABLE worksheet_papers ADD COLUMN updated_at DATETIME NULL AFTER created_at',
  'SELECT "worksheet_papers.updated_at already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'worksheet_papers' AND INDEX_NAME = 'idx_worksheet_papers_level_id') = 0,
  'CREATE INDEX idx_worksheet_papers_level_id ON worksheet_papers (level_id)',
  'SELECT "idx_worksheet_papers_level_id already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'worksheet_papers' AND INDEX_NAME = 'idx_worksheet_papers_topic_id') = 0,
  'CREATE INDEX idx_worksheet_papers_topic_id ON worksheet_papers (topic_id)',
  'SELECT "idx_worksheet_papers_topic_id already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Optional result check: production worksheet_questions should now match localhost-compatible columns.
SELECT COLUMN_NAME
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'worksheet_questions'
ORDER BY ORDINAL_POSITION;
