-- Verification queries for Foundation and Level 1 worksheet production import.
-- Run after schema and content imports.

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

SELECT 'resolved_level_ids' AS check_name, @worksheet_foundation_level_id AS foundation_level_id, @worksheet_level1_id AS level1_id;

SELECT wl.id, wl.level_name
FROM worksheet_levels wl
WHERE wl.id IN (@worksheet_foundation_level_id, @worksheet_level1_id)
ORDER BY wl.level_name;

SELECT wl.level_name, COUNT(DISTINCT wt.id) AS topic_count
FROM worksheet_levels wl
LEFT JOIN worksheet_topics wt ON wt.level_id = wl.id
WHERE wl.id IN (@worksheet_foundation_level_id, @worksheet_level1_id)
GROUP BY wl.id, wl.level_name
ORDER BY wl.level_name;

SELECT wl.level_name, COUNT(DISTINCT wp.id) AS paper_count
FROM worksheet_levels wl
LEFT JOIN worksheet_papers wp ON wp.level_id = wl.id
WHERE wl.id IN (@worksheet_foundation_level_id, @worksheet_level1_id)
GROUP BY wl.id, wl.level_name
ORDER BY wl.level_name;

SELECT wl.level_name, COUNT(wq.id) AS question_count
FROM worksheet_levels wl
LEFT JOIN worksheet_topics wt ON wt.level_id = wl.id
LEFT JOIN worksheet_questions wq ON wq.topic_id = wt.id
WHERE wl.id IN (@worksheet_foundation_level_id, @worksheet_level1_id)
GROUP BY wl.id, wl.level_name
ORDER BY wl.level_name;

SELECT wl.level_name, MIN(topic_question_count) AS min_questions_per_topic, MAX(topic_question_count) AS max_questions_per_topic
FROM worksheet_levels wl
JOIN (
  SELECT wt.level_id, wt.id, COUNT(wq.id) AS topic_question_count
  FROM worksheet_topics wt
  LEFT JOIN worksheet_questions wq ON wq.topic_id = wt.id
  GROUP BY wt.level_id, wt.id
) q ON q.level_id = wl.id
WHERE wl.id IN (@worksheet_foundation_level_id, @worksheet_level1_id)
GROUP BY wl.id, wl.level_name
ORDER BY wl.level_name;

SELECT 'orphan_questions' AS check_name, COUNT(*) AS rows_found
FROM worksheet_questions wq
LEFT JOIN worksheet_topics wt ON wt.id = wq.topic_id
WHERE wt.id IS NULL;

SELECT 'orphan_options' AS check_name, COUNT(*) AS rows_found
FROM question_options qo
LEFT JOIN worksheet_questions wq ON wq.id = qo.question_id
WHERE wq.id IS NULL;
