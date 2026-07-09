<?php

function ensure_worksheet_sub_schema(): void
{
    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS worksheet_levels (
            id VARCHAR(191) NOT NULL PRIMARY KEY,
            level_name VARCHAR(191) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS worksheet_topics (
            id VARCHAR(191) NOT NULL PRIMARY KEY,
            level_id VARCHAR(191) NOT NULL,
            topic_name VARCHAR(255) NOT NULL,
            total_questions INT NOT NULL DEFAULT 0,
            INDEX idx_worksheet_topics_level_id (level_id),
            CONSTRAINT fk_worksheet_topics_level
                FOREIGN KEY (level_id) REFERENCES worksheet_levels(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS worksheet_questions (
            id VARCHAR(191) NOT NULL PRIMARY KEY,
            topic_id VARCHAR(191) NOT NULL,
            question VARCHAR(255) NOT NULL,
            answer VARCHAR(100) NOT NULL,
            INDEX idx_worksheet_questions_topic_id (topic_id),
            CONSTRAINT fk_worksheet_questions_topic
                FOREIGN KEY (topic_id) REFERENCES worksheet_topics(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS worksheet_practices (
            id VARCHAR(191) NOT NULL PRIMARY KEY,
            student_id VARCHAR(191) NOT NULL,
            topic_id VARCHAR(191) NOT NULL,
            score INT NOT NULL DEFAULT 0,
            accuracy DECIMAL(5,2) NOT NULL DEFAULT 0,
            total_questions INT NOT NULL DEFAULT 0,
            correct_answers INT NOT NULL DEFAULT 0,
            time_taken INT NOT NULL DEFAULT 0,
            status VARCHAR(40) NOT NULL DEFAULT "Needs Practice",
            created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
            INDEX idx_worksheet_practices_student_id (student_id),
            INDEX idx_worksheet_practices_topic_id (topic_id),
            CONSTRAINT fk_worksheet_practices_topic
                FOREIGN KEY (topic_id) REFERENCES worksheet_topics(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    if (function_exists('ensure_billing_schema')) {
        ensure_billing_schema();
    }
    $levels = db_all('SELECT id, level_name FROM levels WHERE level_name LIKE "%Worksheet%" ORDER BY level_name ASC');
    foreach ($levels as $level) {
        db_exec_sql(
            'INSERT INTO worksheet_levels (id, level_name)
             VALUES (:id, :level_name)
             ON DUPLICATE KEY UPDATE level_name = VALUES(level_name)',
            ['id' => $level['id'], 'level_name' => $level['level_name']]
        );
    }
}

function worksheet_sub_status(float $accuracy): string
{
    if ($accuracy >= 90) {
        return 'Excellent';
    }
    if ($accuracy >= 70) {
        return 'Good';
    }
    return 'Needs Practice';
}
function worksheet_sub_dynamic_topic_specs(int $levelNumber): array
{
    $specs = [
        2 => [
            ['slug' => 'addition-2-digit-3-row', 'name' => 'Generated Addition - 2 Digit, 3 Row', 'operation' => 'addition', 'rows' => 3, 'digits' => [2, 2, 2]],
            ['slug' => 'subtraction-2-digit-3-row', 'name' => 'Generated Subtraction - 2 Digit, 3 Row', 'operation' => 'subtraction', 'rows' => 3, 'digits' => [2, 2, 2]],
        ],
        3 => [
            ['slug' => 'addition-3-digit-4-row', 'name' => 'Generated Addition - 3 Digit, 4 Row', 'operation' => 'addition', 'rows' => 4, 'digits' => [3, 3, 3, 3]],
            ['slug' => 'subtraction-3-digit-4-row', 'name' => 'Generated Subtraction - 3 Digit, 4 Row', 'operation' => 'subtraction', 'rows' => 4, 'digits' => [3, 3, 3, 3]],
        ],
        4 => [
            ['slug' => 'addition-4-digit-5-row', 'name' => 'Generated Addition - 4 Digit, 5 Row', 'operation' => 'addition', 'rows' => 5, 'digits' => [4, 4, 4, 4, 4]],
            ['slug' => 'subtraction-4-digit-5-row', 'name' => 'Generated Subtraction - 4 Digit, 5 Row', 'operation' => 'subtraction', 'rows' => 5, 'digits' => [4, 4, 4, 4, 4]],
        ],
        5 => [
            ['slug' => 'multiplication-2x1', 'name' => 'Generated Multiplication - 2 Digit x 1 Digit', 'operation' => 'multiplication', 'digits' => [2, 1]],
            ['slug' => 'multiplication-2x2', 'name' => 'Generated Multiplication - 2 Digit x 2 Digit', 'operation' => 'multiplication', 'digits' => [2, 2]],
        ],
        6 => [
            ['slug' => 'division-2-by-1', 'name' => 'Generated Division - 2 Digit by 1 Digit', 'operation' => 'division', 'digits' => [2, 1]],
            ['slug' => 'division-3-by-1', 'name' => 'Generated Division - 3 Digit by 1 Digit', 'operation' => 'division', 'digits' => [3, 1]],
        ],
        7 => [
            ['slug' => 'multiplication-3x2', 'name' => 'Generated Multiplication - 3 Digit x 2 Digit', 'operation' => 'multiplication', 'digits' => [3, 2]],
            ['slug' => 'division-3-by-2', 'name' => 'Generated Division - 3 Digit by 2 Digit', 'operation' => 'division', 'digits' => [3, 2]],
            ['slug' => 'addition-4-digit-6-row', 'name' => 'Generated Addition - 4 Digit, 6 Row', 'operation' => 'addition', 'rows' => 6, 'digits' => [4, 4, 4, 4, 4, 4]],
        ],
    ];

    return $specs[$levelNumber] ?? [];
}

function worksheet_sub_dynamic_topic_id(string $levelId, string $slug): string
{
    return 'dynamic-' . substr(sha1($levelId), 0, 12) . '-' . $slug;
}

function worksheet_sub_ensure_dynamic_topics(array $level): void
{
    $levelName = (string) ($level['level_name'] ?? '');
    if (!str_contains(strtolower($levelName), 'abacus')) {
        return;
    }
    $levelNumber = worksheet_sub_level_number($levelName);
    if ($levelNumber === null || (int) $levelNumber < 2 || (int) $levelNumber > 7) {
        return;
    }

    foreach (worksheet_sub_dynamic_topic_specs((int) $levelNumber) as $spec) {
        db_exec_sql(
            'INSERT INTO worksheet_topics (id, level_id, topic_name, total_questions)
             VALUES (:id, :level_id, :topic_name, 60)
             ON DUPLICATE KEY UPDATE topic_name = VALUES(topic_name), total_questions = 60',
            [
                'id' => worksheet_sub_dynamic_topic_id((string) $level['id'], (string) $spec['slug']),
                'level_id' => $level['id'],
                'topic_name' => $spec['name'],
            ]
        );
    }
}

function worksheet_sub_random_number(int $digits): int
{
    $digits = max(1, $digits);
    if ($digits === 1) {
        return random_int(1, 9);
    }
    return random_int(10 ** ($digits - 1), (10 ** $digits) - 1);
}

function worksheet_sub_make_options(int $answer): array
{
    $options = [$answer];
    $spread = max(5, min(50, abs($answer) + 10));
    while (count($options) < 4) {
        $delta = random_int(1, $spread);
        $candidate = $answer + (random_int(0, 1) ? $delta : -$delta);
        if ($candidate < 0) {
            $candidate = $answer + $delta;
        }
        if (!in_array($candidate, $options, true)) {
            $options[] = $candidate;
        }
    }
    shuffle($options);
    return array_map('strval', $options);
}

function worksheet_sub_render_question(array $numbers, string $operation): string
{
    $symbol = match ($operation) {
        'subtraction' => '-',
        'multiplication' => 'x',
        'division' => '÷',
        default => '+',
    };

    if ($operation === 'multiplication' || $operation === 'division') {
        return $numbers[0] . ' ' . $symbol . ' ' . $numbers[1];
    }

    return implode("\n", array_map(
        static fn(int $number, int $index): string => ($index === 0 ? (string) $number : ($number >= 0 ? '+' . $number : (string) $number)),
        $numbers,
        array_keys($numbers)
    ));
}

function worksheet_sub_generate_dynamic_questions(array $topic): array
{
    $levelNumber = worksheet_sub_level_number((string) ($topic['level_name'] ?? ''));
    if ($levelNumber === null) {
        return [];
    }

    $spec = null;
    foreach (worksheet_sub_dynamic_topic_specs((int) $levelNumber) as $candidate) {
        if (worksheet_sub_dynamic_topic_id((string) $topic['level_id'], (string) $candidate['slug']) === (string) $topic['id']) {
            $spec = $candidate;
            break;
        }
    }
    if (!$spec) {
        return [];
    }

    $questions = [];
    for ($i = 1; $i <= 60; $i++) {
        $operation = (string) $spec['operation'];
        $digits = array_values($spec['digits']);
        if ($operation === 'addition') {
            $numbers = array_map(static fn(int $digit): int => worksheet_sub_random_number($digit), $digits);
            $answer = array_sum($numbers);
        } elseif ($operation === 'subtraction') {
            $first = worksheet_sub_random_number((int) $digits[0]);
            $remaining = $first;
            $numbers = [$first];
            foreach (array_slice($digits, 1) as $index => $digit) {
                $rowsLeft = count($digits) - $index - 2;
                $max = max(1, min((10 ** (int) $digit) - 1, $remaining - $rowsLeft));
                $value = random_int(1, $max);
                $remaining -= $value;
                $numbers[] = -$value;
            }
            $answer = array_sum($numbers);
        } elseif ($operation === 'multiplication') {
            $numbers = [worksheet_sub_random_number((int) $digits[0]), worksheet_sub_random_number((int) $digits[1])];
            $answer = $numbers[0] * $numbers[1];
        } else {
            $divisor = worksheet_sub_random_number((int) $digits[1]);
            $quotient = worksheet_sub_random_number(max(1, (int) $digits[0] - (int) $digits[1] + 1));
            $numbers = [$divisor * $quotient, $divisor];
            $answer = $quotient;
        }

        $questions[] = [
            'id' => (string) $topic['id'] . '-q' . $i . '-' . bin2hex(random_bytes(3)),
            'topic_id' => (string) $topic['id'],
            'question' => worksheet_sub_render_question($numbers, $operation),
            'answer' => (string) $answer,
            'options' => worksheet_sub_make_options((int) $answer),
            'generated' => true,
        ];
    }

    return $questions;
}

function worksheet_sub_level_number(?string $name): ?string
{
    if (!is_string($name) || trim($name) === '') {
        return null;
    }
    if (preg_match('/level\s*0|foundation/i', $name)) {
        return '0';
    }
    if (preg_match('/level\s*(\d+)/i', $name, $m)) {
        return (string) ((int) $m[1]);
    }
    return null;
}

function worksheet_sub_is_active_paid(array $sub): bool
{
    return ($sub['status'] ?? '') === 'active'
        && ($sub['paymentStatus'] ?? '') === 'paid'
        && !empty($sub['expiryDate'])
        && strtotime((string) $sub['expiryDate']) >= time();
}

function worksheet_sub_is_abacus_subscription(array $subscription): bool
{
    $haystack = strtolower(trim(
        (string) ($subscription['planName'] ?? '') . ' ' .
        (string) ($subscription['levelName'] ?? '')
    ));

    return str_contains($haystack, 'abacus') && str_contains($haystack, 'worksheet');
}

function worksheet_sub_match_level(array $subscription): ?array
{
    $levelId = trim((string) ($subscription['levelId'] ?? ''));
    if ($levelId !== '') {
        $level = db_one('SELECT id, level_name FROM worksheet_levels WHERE id = :id LIMIT 1', ['id' => $levelId]);
        if ($level) {
            return $level;
        }
    }

    $levelName = (string) ($subscription['levelName'] ?? '');
    $planName = (string) ($subscription['planName'] ?? '');
    $subscriptionLevelNumber = worksheet_sub_level_number($levelName !== '' ? $levelName : $planName);
    if ($subscriptionLevelNumber === null) {
        return null;
    }

    $levels = db_all('SELECT id, level_name FROM worksheet_levels ORDER BY id ASC');
    foreach ($levels as $level) {
        if (worksheet_sub_level_number((string) ($level['level_name'] ?? '')) === $subscriptionLevelNumber) {
            return $level;
        }
    }

    return null;
}

function worksheet_sub_student_levels(array $student): array
{
    ensure_worksheet_sub_schema();
    $unlocked = [];

    if (function_exists('get_student_subscription_overview') && !empty($student['id'])) {
        $overview = get_student_subscription_overview((string) $student['id']);
        $activeSubscriptions = array_values(array_filter(
            $overview['history'] ?? [],
            static fn(array $sub): bool => worksheet_sub_is_active_paid($sub) && worksheet_sub_is_abacus_subscription($sub)
        ));

        foreach ($activeSubscriptions as $subscription) {
            $level = worksheet_sub_match_level($subscription);
            if ($level) {
                $unlocked[(string) $level['id']] = $level;
            }
        }
    }

    return array_values($unlocked);
}

function worksheet_sub_student_level(array $student, ?string $requestedLevelId = null): ?array
{
    $levels = worksheet_sub_student_levels($student);
    if (!$levels) {
        return null;
    }

    $requestedLevelId = trim((string) $requestedLevelId);
    if ($requestedLevelId !== '') {
        foreach ($levels as $level) {
            if ((string) $level['id'] === $requestedLevelId) {
                return $level;
            }
        }
        return null;
    }

    return $levels[0];
}

function worksheet_sub_request_level_id(): ?string
{
    $levelId = trim((string) ($_GET['levelId'] ?? $_GET['level_id'] ?? ''));
    return $levelId !== '' ? $levelId : null;
}

function worksheet_sub_topic_level_id(string $topicId): ?string
{
    $topic = db_one('SELECT level_id FROM worksheet_topics WHERE id = :id LIMIT 1', ['id' => $topicId]);
    return $topic ? (string) $topic['level_id'] : null;
}

function worksheet_sub_student_has_level(array $student, string $levelId): bool
{
    foreach (worksheet_sub_student_levels($student) as $level) {
        if ((string) $level['id'] === $levelId) {
            return true;
        }
    }
    return false;
}
function worksheet_sub_require_student_level(array $ctx): array
{
    ensure_worksheet_sub_schema();

    $student = current_student($ctx['user']['id']);
    if (!$student) {
        json_response(['message' => 'Student not found'], 404);
    }

    $level = worksheet_sub_student_level($student, worksheet_sub_request_level_id());
    if (!$level) {
        json_response(['message' => 'No active Abacus worksheet subscription level is assigned to this student. Please purchase an Abacus worksheet subscription.'], 403);
    }

    return [$student, $level];
}

function worksheet_sub_require_topic_access(array $ctx, string $topicId): array
{
    ensure_worksheet_sub_schema();

    $student = current_student($ctx['user']['id']);
    if (!$student) {
        json_response(['message' => 'Student not found'], 404);
    }

    $requestedLevelId = worksheet_sub_request_level_id() ?: worksheet_sub_topic_level_id($topicId);
    $level = worksheet_sub_student_level($student, $requestedLevelId);
    if (!$level || !worksheet_sub_student_has_level($student, (string) $level['id'])) {
        json_response(['message' => 'This worksheet level is locked. Please purchase the matching Abacus level subscription.'], 403);
    }

    $topic = db_one(
        'SELECT t.id, t.level_id, t.topic_name, t.total_questions, l.level_name
         FROM worksheet_topics t
         INNER JOIN worksheet_levels l ON l.id = t.level_id
         WHERE t.id = :id AND t.level_id = :level_id
         LIMIT 1',
        ['id' => $topicId, 'level_id' => $level['id']]
    );
    if (!$topic) {
        json_response(['message' => 'Topic not found for your worksheet subscription'], 404);
    }

    return [$student, $level, $topic];
}

function controller_student_worksheet_sub_dashboard(array $ctx): void
{
    [, $level] = worksheet_sub_require_student_level($ctx);
    worksheet_sub_ensure_dynamic_topics($level);

    $topics = db_all(
        'SELECT id, level_id, topic_name, total_questions
         FROM worksheet_topics
         WHERE level_id = :level_id
         ORDER BY id ASC',
        ['level_id' => $level['id']]
    );

    json_response(['level' => $level, 'topics' => $topics]);
}

function controller_student_worksheet_sub_questions(array $ctx, string $topicId): void
{
    [, , $topic] = worksheet_sub_require_topic_access($ctx, $topicId);

    $questions = db_all(
        'SELECT id, topic_id, question, answer
         FROM worksheet_questions
         WHERE topic_id = :topic_id
         ORDER BY id ASC',
        ['topic_id' => $topicId]
    );

    if (!$questions) {
        $questions = worksheet_sub_generate_dynamic_questions($topic);
    }

    $hasGeneratedOptions = $questions && !empty($questions[0]['generated']);
    if ($questions && !$hasGeneratedOptions) {
        $params = [];
        $placeholders = [];
        foreach ($questions as $index => $question) {
            $key = 'question_id_' . $index;
            $params[$key] = $question['id'];
            $placeholders[] = ':' . $key;
            $questions[$index]['options'] = [];
        }

        $options = db_all(
            'SELECT question_id, option_text
             FROM question_options
             WHERE question_id IN (' . implode(',', $placeholders) . ')
             ORDER BY question_id ASC, sort_order ASC',
            $params
        );

        $byQuestion = [];
        foreach ($options as $option) {
            $byQuestion[(string) $option['question_id']][] = (string) $option['option_text'];
        }
        foreach ($questions as $index => $question) {
            $questions[$index]['options'] = $byQuestion[(string) $question['id']] ?? [];
        }
    }

    json_response(['questions' => $questions]);
}

function controller_student_worksheet_sub_practices(array $ctx, string $topicId): void
{
    [$student] = worksheet_sub_require_topic_access($ctx, $topicId);

    $rows = db_all(
        'SELECT id, student_id, topic_id, score, accuracy, total_questions, correct_answers, time_taken, status, created_at
         FROM worksheet_practices
         WHERE student_id = :student_id AND topic_id = :topic_id
         ORDER BY created_at DESC',
        ['student_id' => $student['id'], 'topic_id' => $topicId]
    );

    json_response(['practices' => $rows]);
}

function controller_student_worksheet_sub_save_practice(array $ctx, array $data): void
{
    ensure_worksheet_sub_schema();

    $student = current_student($ctx['user']['id']);
    if (!$student) {
        json_response(['message' => 'Student not found'], 404);
    }

    $topicId = trim((string) ($data['topicId'] ?? ''));
    $totalQuestions = max(0, (int) ($data['totalQuestions'] ?? 0));
    $correctAnswers = max(0, (int) ($data['correctAnswers'] ?? 0));
    $score = max(0, (int) ($data['score'] ?? $correctAnswers));
    $accuracy = max(0, min(100, (float) ($data['accuracy'] ?? 0)));
    $timeTaken = max(0, (int) ($data['timeTaken'] ?? 0));

    if ($topicId === '' || $totalQuestions <= 0) {
        json_response(['message' => 'topicId and totalQuestions are required'], 422);
    }

    worksheet_sub_require_topic_access($ctx, $topicId);

    $id = uuid_v4();
    $now = now_sql();
    db_exec_sql(
        'INSERT INTO worksheet_practices
         (id, student_id, topic_id, score, accuracy, total_questions, correct_answers, time_taken, status, created_at)
         VALUES
         (:id, :student_id, :topic_id, :score, :accuracy, :total_questions, :correct_answers, :time_taken, :status, :created_at)',
        [
            'id' => $id,
            'student_id' => $student['id'],
            'topic_id' => $topicId,
            'score' => $score,
            'accuracy' => $accuracy,
            'total_questions' => $totalQuestions,
            'correct_answers' => $correctAnswers,
            'time_taken' => $timeTaken,
            'status' => worksheet_sub_status($accuracy),
            'created_at' => $now,
        ]
    );

    json_response([
        'practice' => db_one(
            'SELECT id, student_id, topic_id, score, accuracy, total_questions, correct_answers, time_taken, status, created_at
             FROM worksheet_practices WHERE id = :id',
            ['id' => $id]
        ),
    ], 201);
}

function controller_admin_worksheet_sub_levels(): void
{
    ensure_worksheet_sub_schema();
    json_response(['levels' => db_all('SELECT * FROM worksheet_levels ORDER BY id ASC')]);
}

function controller_admin_worksheet_sub_create_level(array $data): void
{
    ensure_worksheet_sub_schema();
    $name = trim((string) ($data['levelName'] ?? $data['level_name'] ?? ''));
    if ($name === '') {
        json_response(['message' => 'levelName is required'], 422);
    }

    $id = uuid_v4();
    db_exec_sql('INSERT INTO worksheet_levels (id, level_name) VALUES (:id, :level_name)', [
        'id' => $id,
        'level_name' => $name,
    ]);
    json_response(['level' => db_one('SELECT * FROM worksheet_levels WHERE id = :id', ['id' => $id])], 201);
}

function controller_admin_worksheet_sub_update_level(string $levelId, array $data): void
{
    ensure_worksheet_sub_schema();
    $name = trim((string) ($data['levelName'] ?? $data['level_name'] ?? ''));
    if ($name === '') {
        json_response(['message' => 'levelName is required'], 422);
    }
    db_exec_sql('UPDATE worksheet_levels SET level_name = :level_name WHERE id = :id', ['level_name' => $name, 'id' => $levelId]);
    json_response(['level' => db_one('SELECT * FROM worksheet_levels WHERE id = :id', ['id' => $levelId])]);
}

function controller_admin_worksheet_sub_delete_level(string $levelId): void
{
    ensure_worksheet_sub_schema();
    db_exec_sql('DELETE FROM worksheet_levels WHERE id = :id', ['id' => $levelId]);
    json_response(['message' => 'Level deleted']);
}

function controller_admin_worksheet_sub_topics(): void
{
    ensure_worksheet_sub_schema();
    $rows = db_all(
        'SELECT t.*, l.level_name
         FROM worksheet_topics t
         LEFT JOIN worksheet_levels l ON l.id = t.level_id
         ORDER BY t.id ASC'
    );
    json_response(['topics' => $rows]);
}

function controller_admin_worksheet_sub_create_topic(array $data): void
{
    ensure_worksheet_sub_schema();
    $levelId = trim((string) ($data['levelId'] ?? $data['level_id'] ?? ''));
    $name = trim((string) ($data['topicName'] ?? $data['topic_name'] ?? ''));
    $total = max(0, (int) ($data['totalQuestions'] ?? $data['total_questions'] ?? 0));
    if ($levelId === '' || $name === '') {
        json_response(['message' => 'levelId and topicName are required'], 422);
    }
    $id = uuid_v4();
    db_exec_sql(
        'INSERT INTO worksheet_topics (id, level_id, topic_name, total_questions) VALUES (:id, :level_id, :topic_name, :total_questions)',
        ['id' => $id, 'level_id' => $levelId, 'topic_name' => $name, 'total_questions' => $total]
    );
    json_response(['topic' => db_one('SELECT * FROM worksheet_topics WHERE id = :id', ['id' => $id])], 201);
}

function controller_admin_worksheet_sub_update_topic(string $topicId, array $data): void
{
    ensure_worksheet_sub_schema();
    $topic = db_one('SELECT * FROM worksheet_topics WHERE id = :id', ['id' => $topicId]);
    if (!$topic) {
        json_response(['message' => 'Topic not found'], 404);
    }
    db_exec_sql(
        'UPDATE worksheet_topics SET level_id = :level_id, topic_name = :topic_name, total_questions = :total_questions WHERE id = :id',
        [
            'level_id' => trim((string) ($data['levelId'] ?? $data['level_id'] ?? $topic['level_id'])),
            'topic_name' => trim((string) ($data['topicName'] ?? $data['topic_name'] ?? $topic['topic_name'])),
            'total_questions' => (int) ($data['totalQuestions'] ?? $data['total_questions'] ?? $topic['total_questions']),
            'id' => $topicId,
        ]
    );
    json_response(['topic' => db_one('SELECT * FROM worksheet_topics WHERE id = :id', ['id' => $topicId])]);
}

function controller_admin_worksheet_sub_delete_topic(string $topicId): void
{
    ensure_worksheet_sub_schema();
    db_exec_sql('DELETE FROM worksheet_topics WHERE id = :id', ['id' => $topicId]);
    json_response(['message' => 'Topic deleted']);
}

function controller_admin_worksheet_sub_questions(string $topicId): void
{
    ensure_worksheet_sub_schema();
    json_response(['questions' => db_all('SELECT * FROM worksheet_questions WHERE topic_id = :topic_id ORDER BY id ASC', ['topic_id' => $topicId])]);
}

function controller_admin_worksheet_sub_create_question(array $data): void
{
    ensure_worksheet_sub_schema();
    $topicId = trim((string) ($data['topicId'] ?? $data['topic_id'] ?? ''));
    $question = trim((string) ($data['question'] ?? ''));
    $answer = trim((string) ($data['answer'] ?? ''));
    if ($topicId === '' || $question === '' || $answer === '') {
        json_response(['message' => 'topicId, question and answer are required'], 422);
    }
    $id = uuid_v4();
    db_exec_sql(
        'INSERT INTO worksheet_questions (id, topic_id, question, answer) VALUES (:id, :topic_id, :question, :answer)',
        ['id' => $id, 'topic_id' => $topicId, 'question' => $question, 'answer' => $answer]
    );
    db_exec_sql('UPDATE worksheet_topics SET total_questions = (SELECT COUNT(*) FROM worksheet_questions WHERE topic_id = :topic_id) WHERE id = :topic_id', ['topic_id' => $topicId]);
    json_response(['question' => db_one('SELECT * FROM worksheet_questions WHERE id = :id', ['id' => $id])], 201);
}

function controller_admin_worksheet_sub_update_question(string $questionId, array $data): void
{
    ensure_worksheet_sub_schema();
    $row = db_one('SELECT * FROM worksheet_questions WHERE id = :id', ['id' => $questionId]);
    if (!$row) {
        json_response(['message' => 'Question not found'], 404);
    }
    db_exec_sql(
        'UPDATE worksheet_questions SET question = :question, answer = :answer WHERE id = :id',
        [
            'question' => trim((string) ($data['question'] ?? $row['question'])),
            'answer' => trim((string) ($data['answer'] ?? $row['answer'])),
            'id' => $questionId,
        ]
    );
    json_response(['question' => db_one('SELECT * FROM worksheet_questions WHERE id = :id', ['id' => $questionId])]);
}

function controller_admin_worksheet_sub_delete_question(string $questionId): void
{
    ensure_worksheet_sub_schema();
    $row = db_one('SELECT topic_id FROM worksheet_questions WHERE id = :id', ['id' => $questionId]);
    db_exec_sql('DELETE FROM worksheet_questions WHERE id = :id', ['id' => $questionId]);
    if ($row) {
        db_exec_sql('UPDATE worksheet_topics SET total_questions = (SELECT COUNT(*) FROM worksheet_questions WHERE topic_id = :topic_id) WHERE id = :topic_id', ['topic_id' => $row['topic_id']]);
    }
    json_response(['message' => 'Question deleted']);
}

function controller_admin_worksheet_sub_reports(): void
{
    ensure_worksheet_sub_schema();
    $topicId = trim((string) ($_GET['topicId'] ?? ''));
    $where = $topicId !== '' ? 'WHERE p.topic_id = :topic_id' : '';
    $params = $topicId !== '' ? ['topic_id' => $topicId] : [];
    $rows = db_all(
        "SELECT p.*, t.topic_name, u.name AS student_name, u.email AS student_email
         FROM worksheet_practices p
         INNER JOIN worksheet_topics t ON t.id = p.topic_id
         LEFT JOIN students s ON s.id = p.student_id
         LEFT JOIN users u ON u.id = s.user_id
         {$where}
         ORDER BY p.created_at DESC",
        $params
    );
    json_response(['reports' => $rows]);
}

function controller_admin_worksheet_sub_upload_csv(): void
{
    ensure_worksheet_sub_schema();
    $topicId = trim((string) ($_POST['topicId'] ?? ''));
    if ($topicId === '' || !isset($_FILES['file']) || !is_array($_FILES['file'])) {
        json_response(['message' => 'topicId and CSV file are required'], 422);
    }
    if (($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        json_response(['message' => 'CSV upload failed'], 400);
    }

    $handle = fopen((string) $_FILES['file']['tmp_name'], 'r');
    if (!$handle) {
        json_response(['message' => 'Unable to read CSV'], 400);
    }

    $created = 0;
    $rowNumber = 0;
    while (($row = fgetcsv($handle)) !== false) {
        $rowNumber++;
        if ($rowNumber === 1 && strtolower(trim((string) ($row[0] ?? ''))) === 'question') {
            continue;
        }
        $question = trim((string) ($row[0] ?? ''));
        $answer = trim((string) ($row[1] ?? ''));
        if ($question === '' || $answer === '') {
            continue;
        }
        db_exec_sql(
            'INSERT INTO worksheet_questions (id, topic_id, question, answer) VALUES (:id, :topic_id, :question, :answer)',
            ['id' => uuid_v4(), 'topic_id' => $topicId, 'question' => $question, 'answer' => $answer]
        );
        $created++;
    }
    fclose($handle);

    db_exec_sql('UPDATE worksheet_topics SET total_questions = (SELECT COUNT(*) FROM worksheet_questions WHERE topic_id = :topic_id) WHERE id = :topic_id', ['topic_id' => $topicId]);
    json_response(['message' => 'CSV imported', 'created' => $created], 201);
}
