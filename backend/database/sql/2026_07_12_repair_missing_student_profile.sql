-- Production repair: create missing Student profile and relink existing paid subscription.
-- Scope: one affected student only.
-- Safe behavior: no DELETE, no DROP, no payment/order/Razorpay changes.
-- Run only after taking a database backup.
-- Live schema confirmed: User table + Student profile table.

START TRANSACTION;

SET @user_id := 'c70a4b4f-620e-4bd6-8f08-f2e5d13c9f70';
SET @now := UTC_TIMESTAMP();

-- 1) Confirm the user exists and is a student.
SELECT id, name, email, role
FROM `User`
WHERE id = @user_id;

-- 2) Reuse existing Student row if present; otherwise create one.
SET @existing_student_id := (
  SELECT id
  FROM `Student`
  WHERE userId = @user_id
  LIMIT 1
);

SET @student_id := COALESCE(@existing_student_id, UUID());

INSERT INTO `Student` (
  id,
  userId,
  tutorId,
  levelId,
  batches,
  subscriptionPlan,
  subscriptionStart,
  subscriptionEnd,
  subscriptionStatus,
  createdAt,
  updatedAt
)
SELECT
  @student_id,
  u.id,
  NULL,
  NULL,
  JSON_ARRAY(),
  NULL,
  NULL,
  NULL,
  'expired',
  @now,
  @now
FROM `User` u
WHERE u.id = @user_id
  AND u.role = 'student'
  AND @existing_student_id IS NULL;

-- 3) If the paid subscription incorrectly used User.id as student_id, relink it to Student.id.
UPDATE student_subscriptions
SET student_id = @student_id,
    updated_at = @now
WHERE student_id = @user_id;

-- 4) Pick the latest active paid/captured subscription for this student.
SET @active_subscription_id := (
  SELECT id
  FROM student_subscriptions
  WHERE student_id = @student_id
    AND status = 'active'
    AND payment_status IN ('paid', 'captured', 'success')
    AND expiry_date >= @now
  ORDER BY expiry_date DESC
  LIMIT 1
);

-- 5) Sync Student profile fields from active subscription.
-- Student.levelId has a foreign key to the old Level table, while worksheet subscriptions may use levels.id.
-- Only write Student.levelId when the purchased level exists in Level; worksheet access remains driven by student_subscriptions.level_id.
UPDATE `Student` s
JOIN student_subscriptions ss ON ss.id = @active_subscription_id
LEFT JOIN `Level` legacy_level ON legacy_level.id = ss.level_id
SET s.levelId = legacy_level.id,
    s.subscriptionPlan = ss.plan_name,
    s.subscriptionStart = ss.start_date,
    s.subscriptionEnd = ss.expiry_date,
    s.subscriptionStatus = 'active',
    s.updatedAt = @now
WHERE s.id = @student_id;

-- 6) Return after-repair data for verification.
SELECT 'student_after_repair' AS result_type,
       s.id,
       s.userId,
       s.levelId,
       s.subscriptionPlan,
       s.subscriptionStart,
       s.subscriptionEnd,
       s.subscriptionStatus
FROM `Student` s
WHERE s.id = @student_id;

SELECT 'subscriptions_after_repair' AS result_type,
       ss.id,
       ss.student_id,
       ss.plan_id,
       ss.level_id,
       ss.plan_name,
       ss.start_date,
       ss.expiry_date,
       ss.status,
       ss.payment_status,
       ss.razorpay_order_id,
       ss.razorpay_payment_id
FROM student_subscriptions ss
WHERE ss.student_id = @student_id
ORDER BY ss.created_at DESC;

COMMIT;