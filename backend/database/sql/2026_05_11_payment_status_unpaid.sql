-- Replace fee/payment "pending" status with "unpaid".
-- Safe to run after the subscription billing migration.

UPDATE student_subscriptions
SET payment_status = 'unpaid'
WHERE payment_status = 'pending';

ALTER TABLE student_subscriptions
  ALTER COLUMN payment_status SET DEFAULT 'unpaid';

UPDATE subscriptions
SET payment_status = 'unpaid'
WHERE payment_status = 'pending';

ALTER TABLE subscriptions
  MODIFY payment_status ENUM('paid','unpaid') DEFAULT 'unpaid';
