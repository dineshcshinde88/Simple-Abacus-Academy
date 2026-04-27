-- Required tables for plain PHP backend (plural table names expected by API code)
-- MariaDB / MySQL

USE abacus_db;

CREATE TABLE IF NOT EXISTS levels (
  id VARCHAR(191) NOT NULL PRIMARY KEY,
  level_name VARCHAR(191) NOT NULL,
  duration INT NOT NULL,
  description TEXT NULL,
  created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  updated_at DATETIME(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS videos (
  id VARCHAR(191) NOT NULL PRIMARY KEY,
  title VARCHAR(191) NOT NULL,
  url TEXT NOT NULL,
  level_id VARCHAR(191) NOT NULL,
  uploaded_by VARCHAR(191) NOT NULL,
  created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  updated_at DATETIME(3) NOT NULL,
  INDEX idx_videos_level_id (level_id),
  INDEX idx_videos_uploaded_by (uploaded_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS worksheets (
  id VARCHAR(191) NOT NULL PRIMARY KEY,
  title VARCHAR(191) NOT NULL,
  pdf_url TEXT NOT NULL,
  level_id VARCHAR(191) NOT NULL,
  created_by VARCHAR(191) NOT NULL,
  created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  updated_at DATETIME(3) NOT NULL,
  INDEX idx_worksheets_level_id (level_id),
  INDEX idx_worksheets_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subscription_plans (
  id VARCHAR(191) NOT NULL PRIMARY KEY,
  name VARCHAR(191) NOT NULL UNIQUE,
  level_id VARCHAR(191) NULL,
  duration_days INT NOT NULL,
  price DOUBLE NOT NULL,
  currency VARCHAR(10) NOT NULL DEFAULT 'INR',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  updated_at DATETIME(3) NOT NULL,
  INDEX idx_subscription_plans_level_id (level_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS video_history (
  id VARCHAR(191) NOT NULL PRIMARY KEY,
  student_id VARCHAR(191) NOT NULL,
  video_id VARCHAR(191) NOT NULL,
  watched_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  progress_percent INT NOT NULL DEFAULT 0,
  created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  updated_at DATETIME(3) NOT NULL,
  UNIQUE KEY uniq_video_history_student_video (student_id, video_id),
  INDEX idx_video_history_student_id (student_id),
  INDEX idx_video_history_video_id (video_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS worksheet_completions (
  id VARCHAR(191) NOT NULL PRIMARY KEY,
  student_id VARCHAR(191) NOT NULL,
  worksheet_id VARCHAR(191) NOT NULL,
  completed_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  score INT NOT NULL DEFAULT 0,
  created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  updated_at DATETIME(3) NOT NULL,
  UNIQUE KEY uniq_worksheet_completions_student_sheet (student_id, worksheet_id),
  INDEX idx_worksheet_completions_student_id (student_id),
  INDEX idx_worksheet_completions_worksheet_id (worksheet_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

