<?php

function controller_student_dashboard(array $ctx): void
{
    $student = current_student($ctx['user']['id']);
    if (!$student) {
        json_response(['message' => 'Student not found'], 404);
    }

    if (function_exists('sync_student_subscription_state')) {
        sync_student_subscription_state((string) $student['id']);
        $student = current_student($ctx['user']['id']);
    }

    $subscriptionOverview = function_exists('get_student_subscription_overview')
        ? get_student_subscription_overview((string) $student['id'])
        : ['current' => null, 'history' => []];
    $activeSubscriptions = array_values(array_filter(
        $subscriptionOverview['history'] ?? [],
        static fn(array $sub): bool => ($sub['status'] ?? '') === 'active'
            && ($sub['paymentStatus'] ?? '') === 'paid'
            && !empty($sub['expiryDate'])
            && strtotime((string) $sub['expiryDate']) >= time()
    ));

    $status = count($activeSubscriptions) > 0 ? 'active' : (string) ($student['subscription_status'] ?? 'expired');
    if ($status !== 'active' && !empty($student['subscription_end']) && strtotime((string) $student['subscription_end']) < time()) {
        $status = 'expired';
        if (($student['subscription_status'] ?? '') !== 'expired') {
            db_exec_sql('UPDATE students SET subscription_status = :status, updated_at = :updated_at WHERE id = :id', [
                'status' => 'expired',
                'updated_at' => now_sql(),
                'id' => $student['id'],
            ]);
        }
    }

    $activeLevelIds = array_values(array_unique(array_filter(array_map(
        static fn(array $sub): ?string => $sub['levelId'] ?? null,
        $activeSubscriptions
    ))));
    $countByLevels = static function (string $table) use ($activeLevelIds, $student): int {
        if ($activeLevelIds) {
            $placeholders = [];
            $params = [];
            foreach ($activeLevelIds as $index => $levelId) {
                $key = 'level_' . $index;
                $placeholders[] = ':' . $key;
                $params[$key] = $levelId;
            }
            return (int) db_value('SELECT COUNT(*) FROM ' . $table . ' WHERE level_id IN (' . implode(',', $placeholders) . ')', $params);
        }
        return !empty($student['level_id']) ? (int) db_value('SELECT COUNT(*) FROM ' . $table . ' WHERE level_id = :id', ['id' => $student['level_id']]) : 0;
    };

    $worksheetsCount = $countByLevels('worksheets');
    $videosCount = $countByLevels('videos');
    $practice = ['purchasedLevels' => 0, 'completedPapers' => 0, 'pendingPapers' => 0, 'averageAccuracy' => 0.0];
    if (function_exists('ensure_practice_schema')) {
        ensure_practice_schema();
        $practiceRow = db_one(
            'SELECT COUNT(DISTINCT sr.paper_id) AS completed_papers, AVG(sr.accuracy) AS average_accuracy
             FROM student_results sr
             WHERE sr.student_id = :student_id',
            ['student_id' => $student['id']]
        ) ?: [];
        $purchased = (int) db_value(
            'SELECT COUNT(DISTINCT level_id)
             FROM student_subscriptions
             WHERE student_id = :student_id AND status = "active" AND payment_status = "paid" AND expiry_date >= :now_ts',
            ['student_id' => $student['id'], 'now_ts' => now_sql()]
        );
        $totalUnlockedPapers = (int) db_value(
            'SELECT COUNT(*)
             FROM practice_papers p
             WHERE p.is_active = 1 AND p.level_id IN (
               SELECT DISTINCT level_id FROM student_subscriptions
               WHERE student_id = :student_id AND status = "active" AND payment_status = "paid" AND expiry_date >= :now_ts
             )',
            ['student_id' => $student['id'], 'now_ts' => now_sql()]
        );
        $completed = (int) ($practiceRow['completed_papers'] ?? 0);
        $practice = [
            'purchasedLevels' => $purchased,
            'completedPapers' => $completed,
            'pendingPapers' => max(0, $totalUnlockedPapers - $completed),
            'averageAccuracy' => round((float) ($practiceRow['average_accuracy'] ?? 0), 2),
        ];
    }

    $batches = [];
    if (!empty($student['batches'])) {
        $decoded = json_decode((string) $student['batches'], true);
        if (is_array($decoded)) {
            $batches = $decoded;
        }
    }
    $activeStartDates = array_filter(array_map(static fn(array $sub): ?string => $sub['startDate'] ?? null, $activeSubscriptions));
    $activeExpiryDates = array_filter(array_map(static fn(array $sub): ?string => $sub['expiryDate'] ?? null, $activeSubscriptions));

    json_response([
        'name' => $student['user_name'] ?? 'Student',
        'level' => $activeSubscriptions
            ? implode(', ', array_values(array_filter(array_map(static fn(array $sub): string => (string) ($sub['levelName'] ?? ''), $activeSubscriptions))))
            : ($student['level_name'] ?? null),
        'batchesCount' => count($batches),
        'worksheetsCount' => $worksheetsCount,
        'videosCount' => $videosCount,
        'subscriptionStatus' => $status,
        'startDate' => $activeStartDates
            ? min($activeStartDates)
            : ($student['subscription_start'] ?? null),
        'expiryDate' => $activeExpiryDates
            ? max($activeExpiryDates)
            : ($student['subscription_end'] ?? null),
        'subscriptions' => $activeSubscriptions,
        'practice' => $practice,
    ]);
}

function controller_student_profile(array $ctx): void
{
    if (function_exists('ensure_student_registration_schema')) {
        ensure_student_registration_schema();
    }

    $student = current_student($ctx['user']['id']);
    if (!$student) {
        json_response(['message' => 'Student not found'], 404);
    }

    if (function_exists('sync_student_subscription_state')) {
        sync_student_subscription_state((string) $student['id']);
        $student = current_student($ctx['user']['id']);
    }

    $subscriptionOverview = function_exists('get_student_subscription_overview')
        ? get_student_subscription_overview((string) $student['id'])
        : ['current' => null, 'history' => []];

    json_response([
        'profile' => [
            'id' => $student['id'],
            'name' => $student['user_name'] ?? '',
            'email' => $student['user_email'] ?? '',
            'course' => $student['course'] ?? '',
            'phoneCountry' => $student['phone_country'] ?? '+91',
            'phone' => $student['phone'] ?? '',
            'gender' => $student['gender'] ?? '',
            'motherTongue' => $student['mother_tongue'] ?? '',
            'dob' => $student['dob'] ?? null,
            'level' => $student['level_name'] ?? null,
            'courseName' => $student['course_name'] ?? null,
            'subscriptionPlan' => $student['subscription_plan'] ?? null,
            'subscriptionStatus' => $student['subscription_status'] ?? 'expired',
            'subscriptionStart' => $student['subscription_start'] ?? null,
            'subscriptionEnd' => $student['subscription_end'] ?? null,
            'createdAt' => $student['created_at'] ?? null,
            'subscriptions' => $subscriptionOverview['history'] ?? [],
        ],
    ]);
}

function controller_student_videos(array $ctx): void
{
    $student = current_student($ctx['user']['id']);
    if (!$student || empty($student['level_id'])) {
        json_response(['message' => 'Level not assigned'], 404);
    }

    $videos = db_all('SELECT * FROM videos WHERE level_id = :id ORDER BY created_at DESC', ['id' => $student['level_id']]);
    json_response(['videos' => $videos]);
}

function controller_student_worksheets(array $ctx): void
{
    $student = current_student($ctx['user']['id']);
    if (!$student || empty($student['level_id'])) {
        json_response(['message' => 'Level not assigned'], 404);
    }

    $worksheets = db_all('SELECT * FROM worksheets WHERE level_id = :id ORDER BY created_at DESC', ['id' => $student['level_id']]);
    json_response(['worksheets' => $worksheets]);
}

function controller_student_upsert_progress(array $ctx, array $data): void
{
    $student = current_student($ctx['user']['id']);
    if (!$student) {
        json_response(['message' => 'Student not found'], 404);
    }

    $levelId = trim((string) ($data['levelId'] ?? $student['level_id'] ?? ''));
    if ($levelId === '') {
        json_response(['message' => 'Level is required for progress tracking'], 400);
    }

    $score = (int) ($data['score'] ?? 0);
    $completed = (int) ($data['completedLessons'] ?? 0);
    $existing = db_one('SELECT id FROM progress WHERE student_id = :student_id AND level_id = :level_id LIMIT 1', [
        'student_id' => $student['id'],
        'level_id' => $levelId,
    ]);

    $now = now_sql();
    if ($existing) {
        db_exec_sql(
            'UPDATE progress SET score = :score, completed_lessons = :completed, updated_at = :updated_at WHERE id = :id',
            ['score' => $score, 'completed' => $completed, 'updated_at' => $now, 'id' => $existing['id']]
        );
        $progress = db_one('SELECT * FROM progress WHERE id = :id', ['id' => $existing['id']]);
    } else {
        $id = uuid_v4();
        db_exec_sql(
            'INSERT INTO progress (id, student_id, level_id, score, completed_lessons, created_at, updated_at)
             VALUES (:id, :student_id, :level_id, :score, :completed, :created_at, :updated_at)',
            [
                'id' => $id,
                'student_id' => $student['id'],
                'level_id' => $levelId,
                'score' => $score,
                'completed' => $completed,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
        $progress = db_one('SELECT * FROM progress WHERE id = :id', ['id' => $id]);
    }

    json_response(['progress' => $progress]);
}

function controller_student_video_history_save(array $ctx, array $data): void
{
    $student = current_student($ctx['user']['id']);
    if (!$student) {
        json_response(['message' => 'Student not found'], 404);
    }

    $videoId = trim((string) ($data['videoId'] ?? ''));
    if ($videoId === '') {
        json_response(['message' => 'videoId is required'], 422);
    }

    $progressPercent = (int) ($data['progressPercent'] ?? 0);
    $existing = db_one('SELECT id FROM video_history WHERE student_id = :student_id AND video_id = :video_id LIMIT 1', [
        'student_id' => $student['id'],
        'video_id' => $videoId,
    ]);

    $now = now_sql();
    if ($existing) {
        db_exec_sql(
            'UPDATE video_history SET watched_at = :watched_at, progress_percent = :progress, updated_at = :updated_at WHERE id = :id',
            ['watched_at' => $now, 'progress' => $progressPercent, 'updated_at' => $now, 'id' => $existing['id']]
        );
        $history = db_one('SELECT * FROM video_history WHERE id = :id', ['id' => $existing['id']]);
    } else {
        $id = uuid_v4();
        db_exec_sql(
            'INSERT INTO video_history (id, student_id, video_id, watched_at, progress_percent, created_at, updated_at)
             VALUES (:id, :student_id, :video_id, :watched_at, :progress, :created_at, :updated_at)',
            [
                'id' => $id,
                'student_id' => $student['id'],
                'video_id' => $videoId,
                'watched_at' => $now,
                'progress' => $progressPercent,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
        $history = db_one('SELECT * FROM video_history WHERE id = :id', ['id' => $id]);
    }

    json_response(['history' => $history], 201);
}

function controller_student_worksheet_completion_save(array $ctx, array $data): void
{
    $student = current_student($ctx['user']['id']);
    if (!$student) {
        json_response(['message' => 'Student not found'], 404);
    }

    $worksheetId = trim((string) ($data['worksheetId'] ?? ''));
    if ($worksheetId === '') {
        json_response(['message' => 'worksheetId is required'], 422);
    }

    $score = (int) ($data['score'] ?? 0);
    $existing = db_one('SELECT id FROM worksheet_completions WHERE student_id = :student_id AND worksheet_id = :worksheet_id LIMIT 1', [
        'student_id' => $student['id'],
        'worksheet_id' => $worksheetId,
    ]);

    $now = now_sql();
    if ($existing) {
        db_exec_sql(
            'UPDATE worksheet_completions SET completed_at = :completed_at, score = :score, updated_at = :updated_at WHERE id = :id',
            ['completed_at' => $now, 'score' => $score, 'updated_at' => $now, 'id' => $existing['id']]
        );
        $completion = db_one('SELECT * FROM worksheet_completions WHERE id = :id', ['id' => $existing['id']]);
    } else {
        $id = uuid_v4();
        db_exec_sql(
            'INSERT INTO worksheet_completions (id, student_id, worksheet_id, completed_at, score, created_at, updated_at)
             VALUES (:id, :student_id, :worksheet_id, :completed_at, :score, :created_at, :updated_at)',
            [
                'id' => $id,
                'student_id' => $student['id'],
                'worksheet_id' => $worksheetId,
                'completed_at' => $now,
                'score' => $score,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
        $completion = db_one('SELECT * FROM worksheet_completions WHERE id = :id', ['id' => $id]);
    }

    json_response(['completion' => $completion], 201);
}

function controller_student_progress_list(array $ctx): void
{
    $student = current_student($ctx['user']['id']);
    if (!$student) {
        json_response(['message' => 'Student not found'], 404);
    }
    $rows = db_all('SELECT * FROM progress WHERE student_id = :student_id', ['student_id' => $student['id']]);
    json_response(['progress' => $rows]);
}

function controller_student_video_history_list(array $ctx): void
{
    $student = current_student($ctx['user']['id']);
    if (!$student) {
        json_response(['message' => 'Student not found'], 404);
    }
    $rows = db_all('SELECT * FROM video_history WHERE student_id = :student_id', ['student_id' => $student['id']]);
    json_response(['history' => $rows]);
}

function controller_student_worksheet_completions_list(array $ctx): void
{
    $student = current_student($ctx['user']['id']);
    if (!$student) {
        json_response(['message' => 'Student not found'], 404);
    }
    $rows = db_all('SELECT * FROM worksheet_completions WHERE student_id = :student_id', ['student_id' => $student['id']]);
    json_response(['completions' => $rows]);
}
