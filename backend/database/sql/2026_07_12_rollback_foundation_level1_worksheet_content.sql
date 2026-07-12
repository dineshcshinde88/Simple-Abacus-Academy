-- Rollback for the Foundation/Level 1 worksheet content import only.
-- Use only if you need to undo the content import before students create new attempts.
-- This does not touch users/students/payments/orders/subscriptions/Razorpay data.

SET @worksheet_foundation_level_id := NULL;
SET @worksheet_level1_id := NULL;

SELECT @worksheet_foundation_level_id := l.id
FROM levels l
LEFT JOIN courses c ON c.id = l.course_id
WHERE (c.slug = 'abacus-worksheet' OR LOWER(c.name) LIKE '%abacus%worksheet%')
  AND (LOWER(l.level_name) LIKE '%foundation%' OR LOWER(l.level_name) LIKE '%level 0%')
ORDER BY l.created_at ASC
LIMIT 1;

SELECT @worksheet_level1_id := l.id
FROM levels l
LEFT JOIN courses c ON c.id = l.course_id
WHERE (c.slug = 'abacus-worksheet' OR LOWER(c.name) LIKE '%abacus%worksheet%')
  AND LOWER(l.level_name) LIKE '%level 1%'
ORDER BY l.created_at ASC
LIMIT 1;

DELETE qo FROM question_options qo
INNER JOIN worksheet_questions wq ON wq.id = qo.question_id
INNER JOIN worksheet_topics wt ON wt.id = wq.topic_id
WHERE wt.level_id IN (@worksheet_foundation_level_id, @worksheet_level1_id);

DELETE wq FROM worksheet_questions wq
INNER JOIN worksheet_topics wt ON wt.id = wq.topic_id
WHERE wt.level_id IN (@worksheet_foundation_level_id, @worksheet_level1_id);

DELETE FROM worksheet_papers
WHERE level_id IN (@worksheet_foundation_level_id, @worksheet_level1_id);

DELETE FROM worksheet_topics
WHERE level_id IN (@worksheet_foundation_level_id, @worksheet_level1_id);

-- Keep worksheet_levels rows because they are mapped to production levels and may be reused by subscriptions.
