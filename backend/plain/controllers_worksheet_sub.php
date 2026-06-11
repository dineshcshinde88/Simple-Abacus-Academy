<?php

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

function worksheet_sub_student_level(array $student): ?array
{
    if (function_exists('get_student_subscription_overview') && !empty($student['id'])) {
        $overview = get_student_subscription_overview((string) $student['id']);
        $activeSubscriptions = array_values(array_filter(
            $overview['history'] ?? [],
            static fn(array $sub): bool => ($sub['status'] ?? '') === 'active'
                && ($sub['paymentStatus'] ?? '') === 'paid'
                && !empty($sub['expiryDate'])
                && strtotime((string) $sub['expiryDate']) >= time()
        ));

        foreach ($activeSubscriptions as $subscription) {
            $planName = (string) ($subscription['planName'] ?? '');
            $levelName = (string) ($subscription['levelName'] ?? '');
            if (!preg_match('/worksheet/i', $planName . ' ' . $levelName)) {
                continue;
            }

            $subscriptionLevelNumber = worksheet_sub_level_number($levelName !== '' ? $levelName : $planName);
            if ($subscriptionLevelNumber === null) {
                continue;
            }

            $levels = db_all('SELECT id, level_name FROM worksheet_levels ORDER BY id ASC');
            foreach ($levels as $level) {
                if (worksheet_sub_level_number((string) ($level['level_name'] ?? '')) === $subscriptionLevelNumber) {
                    return $level;
                }
            }
        }
    }

    if (!empty($student['level_id'])) {
        $level = db_one('SELECT id, level_name FROM worksheet_levels WHERE id = :id LIMIT 1', ['id' => $student['level_id']]);
        if ($level) {
            return $level;
        }
    }

    $studentLevelName = (string) ($student['level_name'] ?? '');
    $studentLevelNumber = worksheet_sub_level_number($studentLevelName);
    if ($studentLevelNumber === null) {
        return null;
    }

    $levels = db_all('SELECT id, level_name FROM worksheet_levels ORDER BY id ASC');
    foreach ($levels as $level) {
        if (worksheet_sub_level_number((string) ($level['level_name'] ?? '')) === $studentLevelNumber) {
            return $level;
        }
    }

    return null;
}

function worksheet_sub_require_student_level(array $ctx): array
{
    $student = current_student($ctx['user']['id']);
    if (!$student) {
        json_response(['message' => 'Student not found'], 404);
    }

    $level = worksheet_sub_student_level($student);
    if (!$level) {
        json_response(['message' => 'No worksheet subscription level is assigned to this student. Please purchase a worksheet subscription.'], 403);
    }

    return [$student, $level];
}

function worksheet_sub_require_topic_access(array $ctx, string $topicId): array
{
    [$student, $level] = worksheet_sub_require_student_level($ctx);
    $topic = db_one(
        'SELECT id, level_id, topic_name, total_questions FROM worksheet_topics WHERE id = :id AND level_id = :level_id LIMIT 1',
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
    worksheet_sub_require_topic_access($ctx, $topicId);

    $questions = db_all(
        'SELECT id, topic_id, question, answer
         FROM worksheet_questions
         WHERE topic_id = :topic_id
         ORDER BY id ASC',
        ['topic_id' => $topicId]
    );

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
    json_response(['levels' => db_all('SELECT * FROM worksheet_levels ORDER BY id ASC')]);
}

function controller_admin_worksheet_sub_create_level(array $data): void
{
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
    $name = trim((string) ($data['levelName'] ?? $data['level_name'] ?? ''));
    if ($name === '') {
        json_response(['message' => 'levelName is required'], 422);
    }
    db_exec_sql('UPDATE worksheet_levels SET level_name = :level_name WHERE id = :id', ['level_name' => $name, 'id' => $levelId]);
    json_response(['level' => db_one('SELECT * FROM worksheet_levels WHERE id = :id', ['id' => $levelId])]);
}

function controller_admin_worksheet_sub_delete_level(string $levelId): void
{
    db_exec_sql('DELETE FROM worksheet_levels WHERE id = :id', ['id' => $levelId]);
    json_response(['message' => 'Level deleted']);
}

function controller_admin_worksheet_sub_topics(): void
{
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
    db_exec_sql('DELETE FROM worksheet_topics WHERE id = :id', ['id' => $topicId]);
    json_response(['message' => 'Topic deleted']);
}

function controller_admin_worksheet_sub_questions(string $topicId): void
{
    json_response(['questions' => db_all('SELECT * FROM worksheet_questions WHERE topic_id = :topic_id ORDER BY id ASC', ['topic_id' => $topicId])]);
}

function controller_admin_worksheet_sub_create_question(array $data): void
{
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
    $row = db_one('SELECT topic_id FROM worksheet_questions WHERE id = :id', ['id' => $questionId]);
    db_exec_sql('DELETE FROM worksheet_questions WHERE id = :id', ['id' => $questionId]);
    if ($row) {
        db_exec_sql('UPDATE worksheet_topics SET total_questions = (SELECT COUNT(*) FROM worksheet_questions WHERE topic_id = :topic_id) WHERE id = :topic_id', ['topic_id' => $row['topic_id']]);
    }
    json_response(['message' => 'Question deleted']);
}

function controller_admin_worksheet_sub_reports(): void
{
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
