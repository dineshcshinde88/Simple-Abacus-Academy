-- Worksheet Subscription module schema and sample data.
-- Import this after the core users/students/levels tables.

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
  question VARCHAR(255) NOT NULL,
  answer VARCHAR(100) NOT NULL,
  INDEX idx_worksheet_questions_topic_id (topic_id),
  CONSTRAINT fk_worksheet_questions_topic
    FOREIGN KEY (topic_id) REFERENCES worksheet_topics(id)
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
  created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  INDEX idx_worksheet_practices_student_id (student_id),
  INDEX idx_worksheet_practices_topic_id (topic_id),
  CONSTRAINT fk_worksheet_practices_topic
    FOREIGN KEY (topic_id) REFERENCES worksheet_topics(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO worksheet_levels (id, level_name) VALUES
('abacus-senior-level-6', 'Abacus Senior - Level 6');

INSERT IGNORE INTO worksheet_topics (id, level_id, topic_name, total_questions) VALUES
('single-double-anzan', 'abacus-senior-level-6', 'Multiplication - Single x Double digit (Anzan)', 12),
('four-digit-add-sub-1', 'abacus-senior-level-6', 'Four digits - Addition / Subtraction (Anzan) - 1', 12),
('four-digit-add-sub-2', 'abacus-senior-level-6', 'Four digits - Addition / Subtraction (Anzan) - 2', 12),
('four-digit-add-sub-3', 'abacus-senior-level-6', 'Four digits - Addition / Subtraction (Anzan) - 3', 12),
('speed-addition-mixed', 'abacus-senior-level-6', 'Speed Addition - Mixed Practice', 12),
('division-basic', 'abacus-senior-level-6', 'Division - Double digit by Single digit', 12);

INSERT IGNORE INTO worksheet_questions (id, topic_id, question, answer) VALUES
('single-double-anzan-q1', 'single-double-anzan', '9 - 4 x 25', '125'),
('single-double-anzan-q2', 'single-double-anzan', '6 x 42', '252'),
('single-double-anzan-q3', 'single-double-anzan', '7 x 36', '252'),
('single-double-anzan-q4', 'single-double-anzan', '8 x 28', '224'),
('single-double-anzan-q5', 'single-double-anzan', '5 x 64', '320'),
('single-double-anzan-q6', 'single-double-anzan', '4 x 78', '312'),
('single-double-anzan-q7', 'single-double-anzan', '3 x 96', '288'),
('single-double-anzan-q8', 'single-double-anzan', '9 x 54', '486'),
('single-double-anzan-q9', 'single-double-anzan', '2 x 87', '174'),
('single-double-anzan-q10', 'single-double-anzan', '6 x 73', '438'),
('four-digit-add-sub-1-q1', 'four-digit-add-sub-1', '1234 + 567', '1801'),
('four-digit-add-sub-1-q2', 'four-digit-add-sub-1', '2450 - 375', '2075'),
('four-digit-add-sub-1-q3', 'four-digit-add-sub-1', '3115 + 689', '3804'),
('four-digit-add-sub-1-q4', 'four-digit-add-sub-1', '4682 - 714', '3968'),
('four-digit-add-sub-2-q1', 'four-digit-add-sub-2', '2145 + 908', '3053'),
('four-digit-add-sub-2-q2', 'four-digit-add-sub-2', '5091 - 846', '4245'),
('four-digit-add-sub-3-q1', 'four-digit-add-sub-3', '3850 + 729', '4579'),
('four-digit-add-sub-3-q2', 'four-digit-add-sub-3', '6420 - 934', '5486'),
('speed-addition-mixed-q1', 'speed-addition-mixed', '128 + 342 + 506', '976'),
('speed-addition-mixed-q2', 'speed-addition-mixed', '815 - 236 + 149', '728'),
('division-basic-q1', 'division-basic', '144 / 6', '24'),
('division-basic-q2', 'division-basic', '216 / 9', '24');
