<?php

function practice_table_has_column(string $table, string $column): bool
{
    $row = db_one(
        'SELECT COUNT(*) AS c
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name',
        ['table_name' => $table, 'column_name' => $column]
    );
    return ((int) ($row['c'] ?? 0)) > 0;
}

function ensure_practice_schema(): void
{
    static $done = false;
    if ($done) {
        return;
    }

    if (function_exists('ensure_billing_schema')) {
        ensure_billing_schema();
    }

    if (!practice_table_has_column('levels', 'slug')) {
        db_exec_sql('ALTER TABLE levels ADD COLUMN slug VARCHAR(191) NULL AFTER level_name');
    }
    if (!practice_table_has_column('levels', 'practice_timer_seconds')) {
        db_exec_sql('ALTER TABLE levels ADD COLUMN practice_timer_seconds INT NOT NULL DEFAULT 180 AFTER slug');
    }
    if (!practice_table_has_column('levels', 'practice_enabled')) {
        db_exec_sql('ALTER TABLE levels ADD COLUMN practice_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER practice_timer_seconds');
    }

    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS subscriptions (
            id CHAR(36) PRIMARY KEY,
            student_id CHAR(36) NOT NULL,
            level_id CHAR(36) NULL,
            status VARCHAR(20) NOT NULL DEFAULT "active",
            payment_status VARCHAR(20) NOT NULL DEFAULT "paid",
            start_date DATETIME NOT NULL,
            expiry_date DATETIME NOT NULL,
            source VARCHAR(50) NOT NULL DEFAULT "practice",
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_subscriptions_student (student_id),
            INDEX idx_subscriptions_level (level_id),
            INDEX idx_subscriptions_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS practice_papers (
            id CHAR(36) PRIMARY KEY,
            level_id CHAR(36) NOT NULL,
            title VARCHAR(255) NOT NULL,
            paper_number INT NOT NULL,
            question_count INT NOT NULL DEFAULT 0,
            timer_seconds INT NOT NULL DEFAULT 180,
            source_file VARCHAR(500) NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY uniq_practice_paper_level_number (level_id, paper_number),
            INDEX idx_practice_papers_level (level_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS practice_questions (
            id CHAR(36) PRIMARY KEY,
            paper_id CHAR(36) NOT NULL,
            question_number INT NOT NULL,
            question_text TEXT NOT NULL,
            options_json TEXT NOT NULL,
            correct_answer VARCHAR(100) NOT NULL,
            explanation TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY uniq_practice_question_paper_number (paper_id, question_number),
            INDEX idx_practice_questions_paper (paper_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS student_results (
            id CHAR(36) PRIMARY KEY,
            student_id CHAR(36) NOT NULL,
            level_id CHAR(36) NOT NULL,
            paper_id CHAR(36) NOT NULL,
            total_questions INT NOT NULL,
            correct_count INT NOT NULL,
            wrong_count INT NOT NULL,
            score INT NOT NULL,
            accuracy DECIMAL(5,2) NOT NULL,
            time_taken_seconds INT NOT NULL DEFAULT 0,
            answers_json LONGTEXT NULL,
            review_json LONGTEXT NULL,
            submitted_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_student_results_student (student_id),
            INDEX idx_student_results_paper (paper_id),
            INDEX idx_student_results_level (level_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS student_progress (
            id CHAR(36) PRIMARY KEY,
            student_id CHAR(36) NOT NULL,
            level_id CHAR(36) NOT NULL,
            paper_id CHAR(36) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT "in_progress",
            answers_json LONGTEXT NULL,
            last_question_number INT NOT NULL DEFAULT 1,
            time_spent_seconds INT NOT NULL DEFAULT 0,
            best_score INT NULL,
            best_accuracy DECIMAL(5,2) NULL,
            attempts_count INT NOT NULL DEFAULT 0,
            started_at DATETIME NOT NULL,
            completed_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY uniq_student_progress_paper (student_id, paper_id),
            INDEX idx_student_progress_student (student_id),
            INDEX idx_student_progress_level (level_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    practice_seed_default_levels();
    $done = true;
}

function practice_slug(string $value): string
{
    $slug = strtolower(trim($value));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?: '';
    return trim($slug, '-');
}

function practice_seed_default_levels(): void
{
    $now = now_sql();
    $defaults = [
        ['name' => 'FOUNDATION', 'slug' => 'foundation'],
        ['name' => 'LEVEL 1', 'slug' => 'level-1'],
    ];

    foreach ($defaults as $item) {
        $level = db_one(
            'SELECT * FROM levels WHERE slug = :slug OR LOWER(level_name) = LOWER(:name) LIMIT 1',
            ['slug' => $item['slug'], 'name' => $item['name']]
        );
        if ($level) {
            db_exec_sql(
                'UPDATE levels
                 SET slug = COALESCE(NULLIF(slug, ""), :slug), practice_timer_seconds = 180, practice_enabled = 1, updated_at = :updated_at
                 WHERE id = :id',
                ['slug' => $item['slug'], 'updated_at' => $now, 'id' => $level['id']]
            );
            continue;
        }

        db_exec_sql(
            'INSERT INTO levels (id, level_name, slug, duration, description, practice_timer_seconds, practice_enabled, created_at, updated_at)
             VALUES (:id, :level_name, :slug, :duration, :description, :timer, 1, :created_at, :updated_at)',
            [
                'id' => uuid_v4(),
                'level_name' => $item['name'],
                'slug' => $item['slug'],
                'duration' => 90,
                'description' => 'Subscription practice level',
                'timer' => 180,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }
}

function practice_current_student(array $ctx): array
{
    $student = current_student($ctx['user']['id']);
    if (!$student) {
        json_response(['message' => 'Student not found'], 404);
    }
    return $student;
}

function student_has_level_subscription(string $studentId, string $levelId): bool
{
    $now = now_sql();
    $active = db_one(
        'SELECT id FROM student_subscriptions
         WHERE student_id = :student_id AND level_id = :level_id
           AND status = "active" AND payment_status = "paid" AND expiry_date >= :now_ts
         LIMIT 1',
        ['student_id' => $studentId, 'level_id' => $levelId, 'now_ts' => $now]
    );
    if ($active) {
        return true;
    }

    $manual = db_one(
        'SELECT id FROM subscriptions
         WHERE student_id = :student_id AND level_id = :level_id
           AND status = "active" AND payment_status = "paid" AND expiry_date >= :now_ts
         LIMIT 1',
        ['student_id' => $studentId, 'level_id' => $levelId, 'now_ts' => $now]
    );
    return (bool) $manual;
}

function require_practice_level_access(string $studentId, string $levelId): void
{
    if (!student_has_level_subscription($studentId, $levelId)) {
        json_response(['message' => 'Purchase subscription to access this level'], 403);
    }
}

function docx_cell_text(DOMElement $cell, DOMXPath $xpath): string
{
    $parts = [];
    foreach ($xpath->query('.//*[local-name()="t"]', $cell) as $node) {
        $parts[] = $node->textContent;
    }
    return trim(preg_replace('/\s+/u', ' ', implode('', $parts)) ?: '');
}

function docx_table_rows(string $filePath): array
{
    if (!class_exists('ZipArchive')) {
        json_response(['message' => 'PHP ZipArchive extension is required to import DOCX files'], 500);
    }
    $zip = new ZipArchive();
    if ($zip->open($filePath) !== true) {
        json_response(['message' => 'Unable to open DOCX file'], 400);
    }
    $xml = $zip->getFromName('word/document.xml');
    $zip->close();
    if (!is_string($xml) || $xml === '') {
        json_response(['message' => 'Invalid DOCX file'], 400);
    }

    $dom = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $dom->loadXML($xml);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    $xpath = new DOMXPath($dom);

    $tables = [];
    foreach ($xpath->query('//*[local-name()="tbl"]') as $table) {
        if (!($table instanceof DOMElement)) {
            continue;
        }
        $rows = [];
        foreach ($xpath->query('./*[local-name()="tr"]', $table) as $tr) {
            if (!($tr instanceof DOMElement)) {
                continue;
            }
            $cells = [];
            foreach ($xpath->query('./*[local-name()="tc"]', $tr) as $tc) {
                if ($tc instanceof DOMElement) {
                    $cells[] = docx_cell_text($tc, $xpath);
                }
            }
            if ($cells) {
                $rows[] = $cells;
            }
        }
        if ($rows) {
            $tables[] = $rows;
        }
    }
    return $tables;
}

function evaluate_abacus_expression(string $expr): int
{
    $clean = preg_replace('/[^0-9+\-]/', '', $expr) ?: '';
    if ($clean === '' || !preg_match('/^[+\-]?\d+(?:[+\-]\d+)*$/', $clean)) {
        return 0;
    }
    preg_match_all('/[+\-]?\d+/', $clean, $m);
    $total = 0;
    foreach ($m[0] as $num) {
        $total += (int) $num;
    }
    return $total;
}

function practice_options_for_answer(int $answer, int $seed): array
{
    $options = [$answer];
    $deltas = [1, -1, 2, -2, 3, -3, 5, -5, 10, -10];
    foreach ($deltas as $delta) {
        $candidate = $answer + $delta;
        if (!in_array($candidate, $options, true)) {
            $options[] = $candidate;
        }
        if (count($options) >= 4) {
            break;
        }
    }
    while (count($options) < 4) {
        $options[] = $answer + count($options) + 7;
    }
    $decorated = [];
    foreach (array_slice($options, 0, 4) as $i => $value) {
        $decorated[] = ['sort' => crc32($seed . ':' . $i . ':' . $value), 'value' => (string) $value];
    }
    usort($decorated, static fn(array $a, array $b): int => $a['sort'] <=> $b['sort']);
    return array_map(static fn(array $row): string => $row['value'], $decorated);
}

function extract_practice_questions_from_table(array $rows): array
{
    $questions = [];
    for ($i = 0; $i < count($rows) - 1; $i += 3) {
        $numberRow = $rows[$i] ?? [];
        $questionRow = $rows[$i + 1] ?? [];
        if (count($numberRow) < 2 || count($questionRow) < 2) {
            continue;
        }
        for ($c = 1; $c < count($numberRow); $c++) {
            $labelNumber = (int) preg_replace('/\D+/', '', (string) ($numberRow[$c] ?? ''));
            $number = count($questions) + 1;
            $question = trim((string) ($questionRow[$c] ?? ''));
            if ($labelNumber <= 0 || $number > 60 || $question === '') {
                continue;
            }
            $answer = evaluate_abacus_expression($question);
            $questions[] = [
                'number' => $number,
                'question' => $question,
                'answer' => (string) $answer,
                'options' => practice_options_for_answer($answer, $number),
            ];
        }
    }
    usort($questions, static fn(array $a, array $b): int => $a['number'] <=> $b['number']);
    return array_slice($questions, 0, 60);
}

function import_practice_docx_for_level(string $levelId, string $filePath): array
{
    ensure_practice_schema();
    if (!is_file($filePath)) {
        json_response(['message' => 'DOCX file not found'], 404);
    }
    $level = db_one('SELECT * FROM levels WHERE id = :id LIMIT 1', ['id' => $levelId]);
    if (!$level) {
        json_response(['message' => 'Level not found'], 404);
    }

    $tables = docx_table_rows($filePath);
    $createdQuestions = 0;
    $createdPapers = 0;
    $now = now_sql();
    $timer = (int) ($level['practice_timer_seconds'] ?? 180);

    foreach (array_slice($tables, 0, 10) as $index => $table) {
        $paperNumber = $index + 1;
        $questions = extract_practice_questions_from_table($table);
        if (!$questions) {
            continue;
        }

        $existing = db_one(
            'SELECT id FROM practice_papers WHERE level_id = :level_id AND paper_number = :paper_number LIMIT 1',
            ['level_id' => $levelId, 'paper_number' => $paperNumber]
        );
        $paperId = $existing['id'] ?? uuid_v4();
        if ($existing) {
            db_exec_sql('DELETE FROM practice_questions WHERE paper_id = :paper_id', ['paper_id' => $paperId]);
            db_exec_sql(
                'UPDATE practice_papers
                 SET title = :title, question_count = :question_count, timer_seconds = :timer_seconds,
                     source_file = :source_file, is_active = 1, updated_at = :updated_at
                 WHERE id = :id',
                [
                    'title' => ($level['level_name'] ?? 'Level') . ' Practice Paper ' . $paperNumber,
                    'question_count' => count($questions),
                    'timer_seconds' => $timer > 0 ? $timer : 180,
                    'source_file' => $filePath,
                    'updated_at' => $now,
                    'id' => $paperId,
                ]
            );
        } else {
            db_exec_sql(
                'INSERT INTO practice_papers
                 (id, level_id, title, paper_number, question_count, timer_seconds, source_file, is_active, created_at, updated_at)
                 VALUES
                 (:id, :level_id, :title, :paper_number, :question_count, :timer_seconds, :source_file, 1, :created_at, :updated_at)',
                [
                    'id' => $paperId,
                    'level_id' => $levelId,
                    'title' => ($level['level_name'] ?? 'Level') . ' Practice Paper ' . $paperNumber,
                    'paper_number' => $paperNumber,
                    'question_count' => count($questions),
                    'timer_seconds' => $timer > 0 ? $timer : 180,
                    'source_file' => $filePath,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
            $createdPapers++;
        }

        foreach ($questions as $q) {
            db_exec_sql(
                'INSERT INTO practice_questions
                 (id, paper_id, question_number, question_text, options_json, correct_answer, explanation, created_at, updated_at)
                 VALUES
                 (:id, :paper_id, :question_number, :question_text, :options_json, :correct_answer, :explanation, :created_at, :updated_at)',
                [
                    'id' => uuid_v4(),
                    'paper_id' => $paperId,
                    'question_number' => $q['number'],
                    'question_text' => $q['question'],
                    'options_json' => json_encode($q['options'], JSON_UNESCAPED_SLASHES),
                    'correct_answer' => $q['answer'],
                    'explanation' => $q['question'] . ' = ' . $q['answer'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
            $createdQuestions++;
        }
    }

    return ['papers' => count(array_slice($tables, 0, 10)), 'createdPapers' => $createdPapers, 'questions' => $createdQuestions];
}

function practice_level_payload(array $level, string $studentId = ''): array
{
    $levelId = (string) $level['id'];
    $papers = db_all(
        'SELECT p.*,
                COALESCE(sp.status, "not_started") AS progress_status,
                sp.best_score, sp.best_accuracy, sp.attempts_count,
                (SELECT COUNT(*) FROM student_results sr WHERE sr.student_id = :student_id AND sr.paper_id = p.id) AS completed_attempts
         FROM practice_papers p
         LEFT JOIN student_progress sp ON sp.paper_id = p.id AND sp.student_id = :student_id2
         WHERE p.level_id = :level_id AND p.is_active = 1
         ORDER BY p.paper_number ASC',
        ['student_id' => $studentId, 'student_id2' => $studentId, 'level_id' => $levelId]
    );
    $unlocked = $studentId !== '' ? student_has_level_subscription($studentId, $levelId) : false;
    return [
        'id' => $levelId,
        'name' => $level['level_name'] ?? '',
        'slug' => $level['slug'] ?? practice_slug((string) ($level['level_name'] ?? '')),
        'timerSeconds' => (int) ($level['practice_timer_seconds'] ?? 180),
        'unlocked' => $unlocked,
        'lockedMessage' => $unlocked ? null : 'Purchase subscription to access this level',
        'papers' => array_map(static fn(array $p): array => [
            'id' => $p['id'],
            'title' => $p['title'],
            'paperNumber' => (int) $p['paper_number'],
            'questionCount' => (int) $p['question_count'],
            'timerSeconds' => (int) $p['timer_seconds'],
            'status' => $p['progress_status'] ?? 'not_started',
            'bestScore' => isset($p['best_score']) ? (int) $p['best_score'] : null,
            'bestAccuracy' => isset($p['best_accuracy']) ? (float) $p['best_accuracy'] : null,
            'attemptsCount' => (int) ($p['attempts_count'] ?? 0),
            'completedAttempts' => (int) ($p['completed_attempts'] ?? 0),
        ], $papers),
    ];
}

function controller_student_practice_levels(array $ctx): void
{
    ensure_practice_schema();
    $student = practice_current_student($ctx);
    $levels = db_all(
        'SELECT * FROM levels
         WHERE practice_enabled = 1 AND (slug IN ("foundation", "level-1") OR level_name IN ("FOUNDATION", "LEVEL 1"))
         ORDER BY CASE WHEN slug = "foundation" THEN 1 WHEN slug = "level-1" THEN 2 ELSE 99 END, level_name'
    );
    $payload = array_map(fn(array $level): array => practice_level_payload($level, (string) $student['id']), $levels);

    $summary = db_one(
        'SELECT
            COUNT(DISTINCT sr.paper_id) AS completed_papers,
            COUNT(sr.id) AS attempts,
            AVG(sr.accuracy) AS average_accuracy,
            MAX(sr.score) AS best_score
         FROM student_results sr
         WHERE sr.student_id = :student_id',
        ['student_id' => $student['id']]
    ) ?: [];

    json_response([
        'levels' => $payload,
        'summary' => [
            'completedPapers' => (int) ($summary['completed_papers'] ?? 0),
            'attempts' => (int) ($summary['attempts'] ?? 0),
            'averageAccuracy' => round((float) ($summary['average_accuracy'] ?? 0), 2),
            'bestScore' => (int) ($summary['best_score'] ?? 0),
        ],
    ]);
}

function shuffled_options_for_attempt(array $options, string $studentId, string $questionId): array
{
    $decorated = [];
    $seed = microtime(true) . ':' . $studentId . ':' . $questionId . ':' . random_int(1, PHP_INT_MAX);
    foreach ($options as $index => $option) {
        $decorated[] = ['sort' => hash('sha256', $seed . ':' . $index . ':' . $option), 'value' => (string) $option];
    }
    usort($decorated, static fn(array $a, array $b): int => strcmp($a['sort'], $b['sort']));
    return array_map(static fn(array $row): string => $row['value'], $decorated);
}

function controller_student_practice_paper(array $ctx, string $paperId): void
{
    ensure_practice_schema();
    $student = practice_current_student($ctx);
    $paper = db_one(
        'SELECT p.*, l.level_name, l.slug
         FROM practice_papers p
         INNER JOIN levels l ON l.id = p.level_id
         WHERE p.id = :id AND p.is_active = 1
         LIMIT 1',
        ['id' => $paperId]
    );
    if (!$paper) {
        json_response(['message' => 'Practice paper not found'], 404);
    }
    require_practice_level_access((string) $student['id'], (string) $paper['level_id']);

    $now = now_sql();
    $progress = db_one(
        'SELECT * FROM student_progress WHERE student_id = :student_id AND paper_id = :paper_id LIMIT 1',
        ['student_id' => $student['id'], 'paper_id' => $paperId]
    );
    if (!$progress) {
        db_exec_sql(
            'INSERT INTO student_progress
             (id, student_id, level_id, paper_id, status, answers_json, last_question_number, time_spent_seconds, attempts_count, started_at, created_at, updated_at)
             VALUES
             (:id, :student_id, :level_id, :paper_id, "in_progress", "{}", 1, 0, 0, :started_at, :created_at, :updated_at)',
            [
                'id' => uuid_v4(),
                'student_id' => $student['id'],
                'level_id' => $paper['level_id'],
                'paper_id' => $paperId,
                'started_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
        $progress = db_one('SELECT * FROM student_progress WHERE student_id = :student_id AND paper_id = :paper_id LIMIT 1', ['student_id' => $student['id'], 'paper_id' => $paperId]);
    }

    $questions = db_all(
        'SELECT id, question_number, question_text, options_json
         FROM practice_questions
         WHERE paper_id = :paper_id
         ORDER BY question_number ASC',
        ['paper_id' => $paperId]
    );

    json_response([
        'paper' => [
            'id' => $paper['id'],
            'title' => $paper['title'],
            'levelId' => $paper['level_id'],
            'levelName' => $paper['level_name'],
            'paperNumber' => (int) $paper['paper_number'],
            'questionCount' => (int) $paper['question_count'],
            'timerSeconds' => (int) $paper['timer_seconds'],
        ],
        'progress' => [
            'status' => $progress['status'] ?? 'in_progress',
            'answers' => json_decode((string) ($progress['answers_json'] ?? '{}'), true) ?: [],
            'lastQuestionNumber' => (int) ($progress['last_question_number'] ?? 1),
            'timeSpentSeconds' => (int) ($progress['time_spent_seconds'] ?? 0),
        ],
        'questions' => array_map(function (array $q) use ($student): array {
            $options = json_decode((string) $q['options_json'], true);
            if (!is_array($options)) {
                $options = [];
            }
            return [
                'id' => $q['id'],
                'questionNumber' => (int) $q['question_number'],
                'questionText' => $q['question_text'],
                'options' => shuffled_options_for_attempt($options, (string) $student['id'], (string) $q['id']),
            ];
        }, $questions),
    ]);
}

function controller_student_practice_save_progress(array $ctx, array $data): void
{
    ensure_practice_schema();
    $student = practice_current_student($ctx);
    $paperId = trim((string) ($data['paperId'] ?? ''));
    if ($paperId === '') {
        json_response(['message' => 'paperId is required'], 422);
    }
    $paper = db_one('SELECT * FROM practice_papers WHERE id = :id LIMIT 1', ['id' => $paperId]);
    if (!$paper) {
        json_response(['message' => 'Practice paper not found'], 404);
    }
    require_practice_level_access((string) $student['id'], (string) $paper['level_id']);

    $answers = is_array($data['answers'] ?? null) ? $data['answers'] : [];
    $lastQuestion = max(1, (int) ($data['lastQuestionNumber'] ?? 1));
    $timeSpent = max(0, (int) ($data['timeSpentSeconds'] ?? 0));
    $now = now_sql();
    db_exec_sql(
        'INSERT INTO student_progress
         (id, student_id, level_id, paper_id, status, answers_json, last_question_number, time_spent_seconds, attempts_count, started_at, created_at, updated_at)
         VALUES
         (:id, :student_id, :level_id, :paper_id, "in_progress", :answers_json, :last_question_number, :time_spent_seconds, 0, :started_at, :created_at, :updated_at)
         ON DUPLICATE KEY UPDATE
           status = IF(status = "submitted", status, "in_progress"),
           answers_json = IF(status = "submitted", answers_json, VALUES(answers_json)),
           last_question_number = IF(status = "submitted", last_question_number, VALUES(last_question_number)),
           time_spent_seconds = IF(status = "submitted", time_spent_seconds, VALUES(time_spent_seconds)),
           updated_at = VALUES(updated_at)',
        [
            'id' => uuid_v4(),
            'student_id' => $student['id'],
            'level_id' => $paper['level_id'],
            'paper_id' => $paperId,
            'answers_json' => json_encode($answers, JSON_UNESCAPED_SLASHES),
            'last_question_number' => $lastQuestion,
            'time_spent_seconds' => $timeSpent,
            'started_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]
    );
    json_response(['message' => 'Progress saved']);
}

function controller_student_practice_submit(array $ctx, array $data): void
{
    ensure_practice_schema();
    $student = practice_current_student($ctx);
    $paperId = trim((string) ($data['paperId'] ?? ''));
    $answers = is_array($data['answers'] ?? null) ? $data['answers'] : [];
    $timeTaken = max(0, (int) ($data['timeTakenSeconds'] ?? 0));
    if ($paperId === '') {
        json_response(['message' => 'paperId is required'], 422);
    }

    $paper = db_one('SELECT * FROM practice_papers WHERE id = :id LIMIT 1', ['id' => $paperId]);
    if (!$paper) {
        json_response(['message' => 'Practice paper not found'], 404);
    }
    require_practice_level_access((string) $student['id'], (string) $paper['level_id']);

    $questions = db_all(
        'SELECT id, question_number, question_text, correct_answer, explanation
         FROM practice_questions
         WHERE paper_id = :paper_id
         ORDER BY question_number ASC',
        ['paper_id' => $paperId]
    );
    $correct = 0;
    $review = [];
    foreach ($questions as $q) {
        $qid = (string) $q['id'];
        $selected = isset($answers[$qid]) ? trim((string) $answers[$qid]) : '';
        $expected = trim((string) $q['correct_answer']);
        $isCorrect = $selected !== '' && $selected === $expected;
        if ($isCorrect) {
            $correct++;
        }
        $review[] = [
            'questionId' => $qid,
            'questionNumber' => (int) $q['question_number'],
            'questionText' => $q['question_text'],
            'selectedAnswer' => $selected,
            'correctAnswer' => $expected,
            'isCorrect' => $isCorrect,
            'explanation' => $q['explanation'],
        ];
    }

    $total = count($questions);
    $wrong = max(0, $total - $correct);
    $accuracy = $total > 0 ? round(($correct / $total) * 100, 2) : 0;
    $resultId = uuid_v4();
    $now = now_sql();
    db_exec_sql(
        'INSERT INTO student_results
         (id, student_id, level_id, paper_id, total_questions, correct_count, wrong_count, score, accuracy,
          time_taken_seconds, answers_json, review_json, submitted_at, created_at, updated_at)
         VALUES
         (:id, :student_id, :level_id, :paper_id, :total_questions, :correct_count, :wrong_count, :score, :accuracy,
          :time_taken_seconds, :answers_json, :review_json, :submitted_at, :created_at, :updated_at)',
        [
            'id' => $resultId,
            'student_id' => $student['id'],
            'level_id' => $paper['level_id'],
            'paper_id' => $paperId,
            'total_questions' => $total,
            'correct_count' => $correct,
            'wrong_count' => $wrong,
            'score' => $correct,
            'accuracy' => $accuracy,
            'time_taken_seconds' => $timeTaken,
            'answers_json' => json_encode($answers, JSON_UNESCAPED_SLASHES),
            'review_json' => json_encode($review, JSON_UNESCAPED_SLASHES),
            'submitted_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]
    );

    db_exec_sql(
        'INSERT INTO student_progress
         (id, student_id, level_id, paper_id, status, answers_json, last_question_number, time_spent_seconds,
          best_score, best_accuracy, attempts_count, started_at, completed_at, created_at, updated_at)
         VALUES
         (:id, :student_id, :level_id, :paper_id, "submitted", :answers_json, :last_question_number, :time_spent_seconds,
          :best_score, :best_accuracy, 1, :started_at, :completed_at, :created_at, :updated_at)
         ON DUPLICATE KEY UPDATE
          status = "submitted",
          answers_json = VALUES(answers_json),
          last_question_number = VALUES(last_question_number),
          time_spent_seconds = VALUES(time_spent_seconds),
          best_score = GREATEST(COALESCE(best_score, 0), VALUES(best_score)),
          best_accuracy = GREATEST(COALESCE(best_accuracy, 0), VALUES(best_accuracy)),
          attempts_count = attempts_count + 1,
          completed_at = VALUES(completed_at),
          updated_at = VALUES(updated_at)',
        [
            'id' => uuid_v4(),
            'student_id' => $student['id'],
            'level_id' => $paper['level_id'],
            'paper_id' => $paperId,
            'answers_json' => json_encode($answers, JSON_UNESCAPED_SLASHES),
            'last_question_number' => $total,
            'time_spent_seconds' => $timeTaken,
            'best_score' => $correct,
            'best_accuracy' => $accuracy,
            'started_at' => $now,
            'completed_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]
    );

    json_response(['message' => 'Practice submitted', 'result' => practice_result_payload($resultId, (string) $student['id'])]);
}

function practice_result_payload(string $resultId, string $studentId = ''): array
{
    $params = ['id' => $resultId];
    $whereStudent = '';
    if ($studentId !== '') {
        $whereStudent = ' AND sr.student_id = :student_id';
        $params['student_id'] = $studentId;
    }
    $row = db_one(
        'SELECT sr.*, p.title AS paper_title, p.paper_number, l.level_name
         FROM student_results sr
         INNER JOIN practice_papers p ON p.id = sr.paper_id
         INNER JOIN levels l ON l.id = sr.level_id
         WHERE sr.id = :id' . $whereStudent . '
         LIMIT 1',
        $params
    );
    if (!$row) {
        json_response(['message' => 'Result not found'], 404);
    }
    return [
        'id' => $row['id'],
        'paperId' => $row['paper_id'],
        'paperTitle' => $row['paper_title'],
        'paperNumber' => (int) $row['paper_number'],
        'levelName' => $row['level_name'],
        'totalQuestions' => (int) $row['total_questions'],
        'correctCount' => (int) $row['correct_count'],
        'wrongCount' => (int) $row['wrong_count'],
        'score' => (int) $row['score'],
        'accuracy' => (float) $row['accuracy'],
        'timeTakenSeconds' => (int) $row['time_taken_seconds'],
        'submittedAt' => $row['submitted_at'],
        'review' => json_decode((string) ($row['review_json'] ?? '[]'), true) ?: [],
    ];
}

function controller_student_practice_result(array $ctx, string $resultId): void
{
    ensure_practice_schema();
    $student = practice_current_student($ctx);
    json_response(['result' => practice_result_payload($resultId, (string) $student['id'])]);
}

function controller_admin_practice_overview(): void
{
    ensure_practice_schema();
    $levels = db_all(
        'SELECT l.*,
                (SELECT COUNT(*) FROM practice_papers p WHERE p.level_id = l.id) AS paper_count,
                (SELECT COUNT(*) FROM practice_questions q INNER JOIN practice_papers p2 ON p2.id = q.paper_id WHERE p2.level_id = l.id) AS question_count
         FROM levels l
         WHERE practice_enabled = 1 OR slug IN ("foundation", "level-1")
         ORDER BY CASE WHEN l.slug = "foundation" THEN 1 WHEN l.slug = "level-1" THEN 2 ELSE 99 END, l.level_name'
    );
    $results = db_all(
        'SELECT sr.*, u.name AS student_name, u.email AS student_email, p.title AS paper_title, l.level_name
         FROM student_results sr
         INNER JOIN students s ON s.id = sr.student_id
         INNER JOIN users u ON u.id = s.user_id
         INNER JOIN practice_papers p ON p.id = sr.paper_id
         INNER JOIN levels l ON l.id = sr.level_id
         ORDER BY sr.submitted_at DESC
         LIMIT 100'
    );
    json_response([
        'levels' => array_map(static fn(array $l): array => [
            'id' => $l['id'],
            'name' => $l['level_name'],
            'slug' => $l['slug'],
            'timerSeconds' => (int) ($l['practice_timer_seconds'] ?? 180),
            'paperCount' => (int) ($l['paper_count'] ?? 0),
            'questionCount' => (int) ($l['question_count'] ?? 0),
        ], $levels),
        'results' => array_map(static fn(array $r): array => [
            'id' => $r['id'],
            'studentName' => $r['student_name'],
            'studentEmail' => $r['student_email'],
            'levelName' => $r['level_name'],
            'paperTitle' => $r['paper_title'],
            'score' => (int) $r['score'],
            'totalQuestions' => (int) $r['total_questions'],
            'accuracy' => (float) $r['accuracy'],
            'timeTakenSeconds' => (int) $r['time_taken_seconds'],
            'submittedAt' => $r['submitted_at'],
        ], $results),
    ]);
}

function controller_admin_practice_import_defaults(): void
{
    ensure_practice_schema();
    $base = __DIR__ . '/../uploads/practise paper/foundation';
    $files = [
        'foundation' => $base . '/SIMPLE ABACUS practice paper - Level FOUNDATION.docx',
        'level-1' => $base . '/SIMPLE ABACUS practice paper - Level 1.docx',
    ];
    $imports = [];
    foreach ($files as $slug => $filePath) {
        $level = db_one('SELECT * FROM levels WHERE slug = :slug LIMIT 1', ['slug' => $slug]);
        if (!$level) {
            continue;
        }
        if (is_file($filePath)) {
            $imports[$slug] = import_practice_docx_for_level((string) $level['id'], $filePath);
        } else {
            $imports[$slug] = ['error' => 'File not found: ' . $filePath];
        }
    }
    json_response(['message' => 'Import complete', 'imports' => $imports]);
}

function controller_admin_practice_upload_docx(): void
{
    ensure_practice_schema();
    $levelId = trim((string) ($_POST['levelId'] ?? ''));
    if ($levelId === '' || !isset($_FILES['file'])) {
        json_response(['message' => 'levelId and DOCX file are required'], 422);
    }
    $file = $_FILES['file'];
    if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        json_response(['message' => 'DOCX upload failed'], 400);
    }
    $name = (string) ($file['name'] ?? 'practice.docx');
    if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'docx') {
        json_response(['message' => 'Only DOCX files are supported'], 422);
    }
    $dir = __DIR__ . '/../uploads/practice-imports';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $target = $dir . '/' . uuid_v4() . '.docx';
    if (!move_uploaded_file((string) $file['tmp_name'], $target)) {
        json_response(['message' => 'Unable to store uploaded DOCX'], 500);
    }
    $result = import_practice_docx_for_level($levelId, $target);
    json_response(['message' => 'DOCX imported', 'import' => $result], 201);
}

function controller_admin_practice_update_level(string $levelId, array $data): void
{
    ensure_practice_schema();
    $timer = max(30, (int) ($data['timerSeconds'] ?? 180));
    $name = trim((string) ($data['name'] ?? ''));
    $level = db_one('SELECT * FROM levels WHERE id = :id LIMIT 1', ['id' => $levelId]);
    if (!$level) {
        json_response(['message' => 'Level not found'], 404);
    }
    db_exec_sql(
        'UPDATE levels
         SET level_name = :level_name, slug = :slug, practice_timer_seconds = :timer, practice_enabled = 1, updated_at = :updated_at
         WHERE id = :id',
        [
            'level_name' => $name !== '' ? $name : $level['level_name'],
            'slug' => practice_slug($name !== '' ? $name : (string) $level['level_name']),
            'timer' => $timer,
            'updated_at' => now_sql(),
            'id' => $levelId,
        ]
    );
    db_exec_sql('UPDATE practice_papers SET timer_seconds = :timer, updated_at = :updated_at WHERE level_id = :level_id', [
        'timer' => $timer,
        'updated_at' => now_sql(),
        'level_id' => $levelId,
    ]);
    json_response(['level' => db_one('SELECT * FROM levels WHERE id = :id LIMIT 1', ['id' => $levelId])]);
}
