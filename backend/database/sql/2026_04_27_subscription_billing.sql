-- Razorpay + level-wise subscription + reminder support
-- Safe to run on existing schema (idempotent where possible).

CREATE TABLE IF NOT EXISTS payment_gateway_configs (
  id CHAR(36) PRIMARY KEY,
  provider VARCHAR(50) NOT NULL UNIQUE,
  key_id VARCHAR(255) NOT NULL,
  secret_enc TEXT NULL,
  enabled TINYINT(1) NOT NULL DEFAULT 1,
  created_by CHAR(36) NULL,
  updated_by CHAR(36) NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_attempts (
  id CHAR(36) PRIMARY KEY,
  student_id CHAR(36) NOT NULL,
  plan_id CHAR(36) NULL,
  provider VARCHAR(50) NOT NULL,
  amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  currency VARCHAR(10) NOT NULL DEFAULT 'INR',
  status VARCHAR(20) NOT NULL DEFAULT 'created',
  provider_order_id VARCHAR(120) NULL,
  provider_payment_id VARCHAR(120) NULL,
  provider_signature VARCHAR(255) NULL,
  paid_at DATETIME NULL,
  metadata_json LONGTEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_payment_attempts_student (student_id),
  INDEX idx_payment_attempts_provider_order (provider_order_id),
  INDEX idx_payment_attempts_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_subscriptions (
  id CHAR(36) PRIMARY KEY,
  student_id CHAR(36) NOT NULL,
  plan_id CHAR(36) NULL,
  level_id CHAR(36) NULL,
  plan_name VARCHAR(255) NOT NULL,
  amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  currency VARCHAR(10) NOT NULL DEFAULT 'INR',
  start_date DATETIME NOT NULL,
  expiry_date DATETIME NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'active',
  payment_status VARCHAR(20) NOT NULL DEFAULT 'unpaid',
  payment_attempt_id CHAR(36) NULL,
  razorpay_order_id VARCHAR(120) NULL,
  razorpay_payment_id VARCHAR(120) NULL,
  notes TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_student_subscriptions_student (student_id),
  INDEX idx_student_subscriptions_level (level_id),
  INDEX idx_student_subscriptions_status (status),
  INDEX idx_student_subscriptions_expiry (expiry_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subscription_reminders (
  id CHAR(36) PRIMARY KEY,
  subscription_id CHAR(36) NOT NULL,
  student_id CHAR(36) NOT NULL,
  reminder_type VARCHAR(30) NOT NULL,
  channel VARCHAR(20) NOT NULL,
  sent_to VARCHAR(255) NULL,
  message TEXT NULL,
  sent_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uniq_subscription_reminder (subscription_id, reminder_type, channel),
  INDEX idx_subscription_reminders_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE subscription_plans
  ADD COLUMN IF NOT EXISTS level_id CHAR(36) NULL AFTER name,
  ADD COLUMN IF NOT EXISTS currency VARCHAR(10) NOT NULL DEFAULT 'INR' AFTER price,
  ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER currency;
