-- Non-destructive multi-plan worksheet checkout schema.
CREATE TABLE IF NOT EXISTS subscription_orders (
  id CHAR(36) PRIMARY KEY,
  payment_attempt_id CHAR(36) NOT NULL UNIQUE,
  student_id CHAR(36) NOT NULL,
  provider VARCHAR(50) NOT NULL DEFAULT 'razorpay',
  provider_order_id VARCHAR(120) NULL UNIQUE,
  provider_payment_id VARCHAR(120) NULL,
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
  discount DECIMAL(10,2) NOT NULL DEFAULT 0,
  total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  currency VARCHAR(10) NOT NULL DEFAULT 'INR',
  payment_status VARCHAR(40) NOT NULL DEFAULT 'created',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  paid_at DATETIME NULL,
  INDEX idx_subscription_orders_student (student_id),
  INDEX idx_subscription_orders_status (payment_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subscription_order_items (
  id CHAR(36) PRIMARY KEY,
  order_id CHAR(36) NOT NULL,
  product_id CHAR(36) NULL,
  plan_id CHAR(36) NOT NULL,
  program_type VARCHAR(30) NOT NULL,
  level_id CHAR(36) NOT NULL,
  unit_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  duration_days INT NOT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'pending',
  subscription_id CHAR(36) NULL,
  activation_error TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uniq_subscription_order_plan (order_id, plan_id),
  UNIQUE KEY uniq_subscription_order_subscription (subscription_id),
  INDEX idx_subscription_order_items_order (order_id),
  INDEX idx_subscription_order_items_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rollback (only if no multi-plan orders need to be retained):
-- DROP TABLE subscription_order_items;
-- DROP TABLE subscription_orders;