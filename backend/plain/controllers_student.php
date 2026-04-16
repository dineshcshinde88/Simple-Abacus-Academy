<?php

function controller_student_dashboard(array $ctx): void
{
    $student = current_student($ctx['user']['id']);
    if (!$student) {
        json_response(['message' => 'Student not found'], 404);
    }

    $status = (string) ($student['subscription_status'] ?? 'expired');
    if (!empty($student['subscription_end']) && strtotime((string) $student['subscription_end']) < time()) {
        $status = 'expired';
        if (($student['subscription_status'] ?? '') !== 'expired') {
            db_exec_sql('UPDATE students SET subscription_status = :status, updated_at = :updated_at WHERE id = :id', [
                'status' => 'expired',
                'updated_at' => now_sql(),
                'id' => $student['id'],
            ]);
        }
    }

    $worksheetsCount = !empty($student['level_id']) ? (int) db_value('SELECT COUNT(*) FROM worksheets WHERE level_id = :id', ['id' => $student['level_id']]) : 0;
    $videosCount = !empty($student['level_id']) ? (int) db_value('SELECT COUNT(*) FROM videos WHERE level_id = :id', ['id' => $student['level_id']]) : 0;

    $batches = [];
    if (!empty($student['batches'])) {
        $decoded = json_decode((string) $student['batches'], true);
        if (is_array($decoded)) {
            $batches = $decoded;
        }
    }

    json_response([
        'name' => $student['user_name'] ?? 'Student',
        'level' => $student['level_name'] ?? null,
        'batchesCount' => count($batches),
        'worksheetsCount' => $worksheetsCount,
        'videosCount' => $videosCount,
        'subscriptionStatus' => $status,
        'expiryDate' => $student['subscription_end'] ?? null,
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

