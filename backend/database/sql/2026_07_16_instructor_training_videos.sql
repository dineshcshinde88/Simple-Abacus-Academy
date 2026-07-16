CREATE TABLE IF NOT EXISTS instructor_video_subscriptions (
  id CHAR(36) PRIMARY KEY,
  instructor_id CHAR(36) NOT NULL,
  plan_name VARCHAR(80) NOT NULL DEFAULT '90 Days',
  duration_days INT NOT NULL DEFAULT 90,
  payment_method VARCHAR(40) NULL,
  payment_amount DECIMAL(10,2) NULL,
  payment_reference VARCHAR(255) NULL,
  payment_note TEXT NULL,
  start_date DATETIME NOT NULL,
  expiry_date DATETIME NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'pending',
  activated_by_admin_id VARCHAR(64) NULL,
  activated_at DATETIME NULL,
  admin_note TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_ivs_instructor_status (instructor_id, status),
  INDEX idx_ivs_expiry (expiry_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS instructor_training_videos (
  id CHAR(36) PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  program VARCHAR(40) NOT NULL,
  level VARCHAR(80) NOT NULL,
  sequence_number INT NOT NULL DEFAULT 1,
  cloudinary_public_id VARCHAR(255) NOT NULL,
  thumbnail VARCHAR(500) NULL,
  duration_seconds INT NOT NULL DEFAULT 0,
  status VARCHAR(30) NOT NULL DEFAULT 'published',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_itv_library (program, level, sequence_number),
  INDEX idx_itv_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS instructor_video_progress (
  id CHAR(36) PRIMARY KEY,
  instructor_id CHAR(36) NOT NULL,
  video_id CHAR(36) NOT NULL,
  subscription_id CHAR(36) NULL,
  current_position_seconds INT NOT NULL DEFAULT 0,
  maximum_watched_position_seconds INT NOT NULL DEFAULT 0,
  unique_watched_seconds INT NOT NULL DEFAULT 0,
  duration_seconds INT NOT NULL DEFAULT 0,
  completion_percentage DECIMAL(5,2) NOT NULL DEFAULT 0,
  is_completed TINYINT(1) NOT NULL DEFAULT 0,
  completed_at DATETIME NULL,
  last_watched_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uniq_ivp_instructor_video (instructor_id, video_id),
  INDEX idx_ivp_subscription (subscription_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS instructor_video_watch_segments (
  id CHAR(36) PRIMARY KEY,
  instructor_id CHAR(36) NOT NULL,
  video_id CHAR(36) NOT NULL,
  segment_start INT NOT NULL,
  segment_end INT NOT NULL,
  session_id VARCHAR(80) NOT NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_ivws_lookup (instructor_id, video_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
