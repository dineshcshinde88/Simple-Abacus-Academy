<?php

function instructor_video_table_has_column(string $table, string $column): bool
{
    return (int) db_value(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column',
        ['table' => $table, 'column' => $column]
    ) > 0;
}

function ensure_instructor_video_schema(): void
{
    db_exec_sql(
        "CREATE TABLE IF NOT EXISTS instructor_video_subscriptions (
            id CHAR(36) PRIMARY KEY,
            instructor_id CHAR(36) NOT NULL,
            program VARCHAR(40) NOT NULL DEFAULT 'abacus',
            plan_name VARCHAR(80) NOT NULL DEFAULT '90 Days',
            duration_days INT NOT NULL DEFAULT 90,
            payment_method VARCHAR(40) NULL,
            payment_amount DECIMAL(10,2) NULL,
            payment_reference VARCHAR(255) NULL,
            payment_note TEXT NULL,
            start_date DATETIME NOT NULL,
            expiry_date DATETIME NOT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'pending',
            activated_by_admin_id VARCHAR(64) NULL,
            activated_at DATETIME NULL,
            admin_note TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_ivs_instructor_status (instructor_id, status),
            INDEX idx_ivs_expiry (expiry_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    if (!instructor_video_table_has_column('instructor_video_subscriptions', 'program')) {
        db_exec_sql("ALTER TABLE instructor_video_subscriptions ADD COLUMN program VARCHAR(40) NOT NULL DEFAULT 'abacus' AFTER instructor_id");
    }

    db_exec_sql(
        "CREATE TABLE IF NOT EXISTS instructor_training_videos (
            id CHAR(36) PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT NULL,
            program VARCHAR(40) NOT NULL,
            level VARCHAR(80) NOT NULL,
            sequence_number INT NOT NULL DEFAULT 1,
            cloudinary_public_id VARCHAR(255) NOT NULL,
            thumbnail VARCHAR(500) NULL,
            duration_seconds INT NOT NULL DEFAULT 0,
            status VARCHAR(30) NOT NULL DEFAULT 'published',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_itv_library (program, level, sequence_number),
            INDEX idx_itv_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    db_exec_sql(
        "CREATE TABLE IF NOT EXISTS instructor_video_progress (
            id CHAR(36) PRIMARY KEY,
            instructor_id CHAR(36) NOT NULL,
            video_id CHAR(36) NOT NULL,
            subscription_id CHAR(36) NULL,
            current_position_seconds INT NOT NULL DEFAULT 0,
            maximum_watched_position_seconds INT NOT NULL DEFAULT 0,
            unique_watched_seconds INT NOT NULL DEFAULT 0,
            duration_seconds INT NOT NULL DEFAULT 0,
            completion_percentage DECIMAL(5,2) NOT NULL DEFAULT 0,
            is_completed TINYINT(1) NOT NULL DEFAULT 0,
            completed_at DATETIME NULL,
            last_watched_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY uniq_ivp_instructor_video (instructor_id, video_id),
            INDEX idx_ivp_subscription (subscription_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    db_exec_sql(
        "CREATE TABLE IF NOT EXISTS instructor_video_watch_segments (
            id CHAR(36) PRIMARY KEY,
            instructor_id CHAR(36) NOT NULL,
            video_id CHAR(36) NOT NULL,
            segment_start INT NOT NULL,
            segment_end INT NOT NULL,
            session_id VARCHAR(80) NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_ivws_lookup (instructor_id, video_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    db_exec_sql("UPDATE instructor_training_videos SET level = 'Level 1' WHERE program = 'abacus' AND level = 'Foundation'");
    db_exec_sql("UPDATE instructor_training_videos
        SET level = CASE
            WHEN UPPER(title) REGEXP 'LEVEL[[:space:]]*8([^0-9]|$)' THEN 'Level 8'
            WHEN UPPER(title) REGEXP 'LEVEL[[:space:]]*7([^0-9]|$)' THEN 'Level 7'
            WHEN UPPER(title) REGEXP 'LEVEL[[:space:]]*6([^0-9]|$)' THEN 'Level 6'
            WHEN UPPER(title) REGEXP 'LEVEL[[:space:]]*5([^0-9]|$)' THEN 'Level 5'
            WHEN UPPER(title) REGEXP 'LEVEL[[:space:]]*4([^0-9]|$)' THEN 'Level 4'
            WHEN UPPER(title) REGEXP 'LEVEL[[:space:]]*3([^0-9]|$)' THEN 'Level 3'
            WHEN UPPER(title) REGEXP 'LEVEL[[:space:]]*2([^0-9]|$)' THEN 'Level 2'
            WHEN UPPER(title) REGEXP 'LEVEL[[:space:]]*1([^0-9]|$)' THEN 'Level 1'
            ELSE level
        END
        WHERE program = 'abacus' AND UPPER(title) REGEXP 'LEVEL[[:space:]]*[1-8]([^0-9]|$)'");
}

function instructor_video_context(array $ctx): array
{
    ensure_instructor_auth_schema();
    ensure_instructor_video_schema();

    $user = $ctx['user'];
    $instructor = db_one('SELECT * FROM instructors WHERE email = :email LIMIT 1', ['email' => strtolower((string) $user['email'])]);
    if (!$instructor) {
        json_response(['message' => 'Instructor profile was not found for this login.'], 403);
    }

    return ['user' => $user, 'instructor' => $instructor];
}

function instructor_video_sync_expired(string $instructorId = ''): void
{
    $params = ['now' => now_sql()];
    $where = "status = 'active' AND expiry_date < :now";
    if ($instructorId !== '') {
        $where .= ' AND instructor_id = :instructor_id';
        $params['instructor_id'] = $instructorId;
    }
    db_exec_sql("UPDATE instructor_video_subscriptions SET status = 'expired', updated_at = :now WHERE {$where}", $params);
}

function instructor_video_active_subscription(string $instructorId, string $program = ''): ?array
{
    instructor_video_sync_expired($instructorId);
    $programWhere = $program !== '' ? ' AND program = :program' : '';
    $params = ['instructor_id' => $instructorId, 'now' => now_sql()];
    if ($program !== '') {
        $params['program'] = $program;
    }
    return db_one(
        "SELECT * FROM instructor_video_subscriptions
         WHERE instructor_id = :instructor_id
           AND status = 'active'
           AND start_date <= :now
           AND expiry_date >= :now
           {$programWhere}
         ORDER BY expiry_date DESC
         LIMIT 1",
        $params
    );
}

function instructor_video_active_subscriptions(string $instructorId): array
{
    instructor_video_sync_expired($instructorId);
    return db_all(
        "SELECT * FROM instructor_video_subscriptions
         WHERE instructor_id = :instructor_id
           AND status = 'active'
           AND start_date <= :now
           AND expiry_date >= :now
         ORDER BY expiry_date DESC",
        ['instructor_id' => $instructorId, 'now' => now_sql()]
    );
}

function instructor_video_subscription_payload(string $instructorId): array
{
    $activeSubscriptions = instructor_video_active_subscriptions($instructorId);
    $active = $activeSubscriptions[0] ?? null;
    $latest = $active ?: db_one(
        'SELECT * FROM instructor_video_subscriptions WHERE instructor_id = :instructor_id ORDER BY created_at DESC LIMIT 1',
        ['instructor_id' => $instructorId]
    );
    $status = 'none';
    if ($latest) {
        $status = (string) $latest['status'];
    }

    $remaining = 0;
    if ($active) {
        $remaining = max(0, (int) ceil((strtotime((string) $active['expiry_date']) - time()) / 86400));
    }

    return [
        'hasAccess' => count($activeSubscriptions) > 0,
        'state' => $active ? 'active' : ($status === 'expired' ? 'expired' : ($status === 'suspended' ? 'suspended' : 'none')),
        'subscription' => $latest ? [
            'id' => $latest['id'],
            'planName' => $latest['plan_name'],
            'startDate' => $latest['start_date'],
            'expiryDate' => $latest['expiry_date'],
            'status' => $latest['status'],
            'remainingDays' => $remaining,
            'program' => (string) ($latest['program'] ?? 'abacus'),
        ] : null,
        'subscriptions' => array_map(function (array $item): array {
            return [
                'id' => $item['id'],
                'planName' => $item['plan_name'],
                'startDate' => $item['start_date'],
                'expiryDate' => $item['expiry_date'],
                'status' => $item['status'],
                'remainingDays' => max(0, (int) ceil((strtotime((string) $item['expiry_date']) - time()) / 86400)),
                'program' => (string) ($item['program'] ?? 'abacus'),
            ];
        }, $activeSubscriptions),
    ];
}

function instructor_video_group_key(array $video): string
{
    return strtolower((string) $video['program']) . '|' . strtolower((string) $video['level']);
}

function instructor_video_library(string $instructorId, array $subscriptions): array
{
    $programs = array_values(array_unique(array_map(fn(array $item): string => (string) ($item['program'] ?? ''), $subscriptions)));
    $hasAbacus = in_array('abacus', $programs, true) ? 1 : 0;
    $hasVedic = in_array('vedic_maths', $programs, true) ? 1 : 0;
    $videos = db_all(
        "SELECT v.*,
                p.current_position_seconds,
                p.maximum_watched_position_seconds,
                p.unique_watched_seconds,
                p.completion_percentage,
                p.is_completed,
                p.completed_at
         FROM instructor_training_videos v
         LEFT JOIN instructor_video_progress p ON p.video_id = v.id AND p.instructor_id = :instructor_id
         WHERE v.status = 'published'
           AND ((:has_abacus = 1 AND v.program = 'abacus') OR (:has_vedic = 1 AND v.program = 'vedic_maths'))
         ORDER BY v.program,
                  CASE v.level
                    WHEN 'Level 1' THEN 1 WHEN 'Level 2' THEN 2
                    WHEN 'Level 3' THEN 3 WHEN 'Level 4' THEN 4
                    WHEN 'Level 5' THEN 5 WHEN 'Level 6' THEN 6
                    WHEN 'Level 7' THEN 7 WHEN 'Level 8' THEN 8
                    ELSE 99
                  END,
                  v.sequence_number,
                  v.created_at,
                  v.id",
        ['instructor_id' => $instructorId, 'has_abacus' => $hasAbacus, 'has_vedic' => $hasVedic]
    );

    $programChainComplete = [];
    $completed = 0;
    $items = [];
    foreach ($videos as $video) {
        $program = strtolower((string) $video['program']);
        $sequence = (int) $video['sequence_number'];
        $isCompleted = (int) ($video['is_completed'] ?? 0) === 1;
        $previousLessonsComplete = $programChainComplete[$program] ?? true;
        $isUnlocked = $previousLessonsComplete;
        if ($isCompleted) {
            $completed += 1;
        }
        $programChainComplete[$program] = $previousLessonsComplete && $isCompleted;

        $items[] = [
            'id' => $video['id'],
            'title' => $video['title'],
            'description' => $video['description'],
            'program' => $video['program'],
            'level' => $video['level'],
            'sequenceNumber' => $sequence,
            'thumbnail' => $video['thumbnail'],
            'durationSeconds' => (int) $video['duration_seconds'],
            'isUnlocked' => $isUnlocked,
            'lockedReason' => 'Complete the previous video to unlock this lesson.',
            'progress' => [
                'currentPositionSeconds' => (int) ($video['current_position_seconds'] ?? 0),
                'maximumWatchedPositionSeconds' => (int) ($video['maximum_watched_position_seconds'] ?? 0),
                'uniqueWatchedSeconds' => (int) ($video['unique_watched_seconds'] ?? 0),
                'completionPercentage' => (float) ($video['completion_percentage'] ?? 0),
                'isCompleted' => $isCompleted,
                'completedAt' => $video['completed_at'] ?? null,
            ],
        ];
    }

    $total = count($items);
    return [
        'videos' => $items,
        'summary' => [
            'totalVideos' => $total,
            'completedVideos' => $completed,
            'remainingVideos' => max(0, $total - $completed),
            'overallProgress' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
        ],
    ];
}

function instructor_video_find_unlocked(string $instructorId, string $videoId, array $subscriptions): array
{
    if (!$subscriptions) {
        json_response(['message' => 'Training Video subscription is not active.'], 403);
    }
    $library = instructor_video_library($instructorId, $subscriptions);
    foreach ($library['videos'] as $video) {
        if ($video['id'] === $videoId) {
            if (!$video['isUnlocked']) {
                json_response(['message' => $video['lockedReason']], 403);
            }
            return $video;
        }
    }
    json_response(['message' => 'Video not found.'], 404);
}

function controller_instructor_video_dashboard(array $ctx): void
{
    $iv = instructor_video_context($ctx);
    $instructorId = (string) $iv['instructor']['id'];
    $subscriptions = instructor_video_active_subscriptions($instructorId);
    $subscriptionPayload = instructor_video_subscription_payload($instructorId);
    $library = instructor_video_library($instructorId, $subscriptions);

    json_response([
        'subscription' => $subscriptionPayload,
        'library' => $library,
        'watermarkIdentity' => [
            'name' => $iv['instructor']['full_name'] ?: $iv['user']['name'],
            'mobile' => $iv['instructor']['mobile'] ?? '',
            'instructorId' => $instructorId,
        ],
    ]);
}

function cloudinary_video_public_id(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    // Accept either the public ID copied from Cloudinary or a complete
    // Cloudinary delivery URL pasted by an administrator.
    if (preg_match('#^https?://#i', $value) === 1) {
        $path = (string) (parse_url($value, PHP_URL_PATH) ?? '');
        if (preg_match('#/video/(?:upload|private|authenticated)/(?:s--[^/]+--/)?(?:v[0-9]+/)?(.+)$#', $path, $matches) === 1) {
            $value = rawurldecode($matches[1]);
        }
    }

    $value = trim(str_replace('\\', '/', $value), '/');
    return preg_replace('/\.(?:mp4|mov|m4v|webm)$/i', '', $value) ?? '';
}

function cloudinary_video_asset_details(string $publicId): ?array
{
    $cloud = trim((string) envv('CLOUDINARY_CLOUD_NAME', ''));
    $apiKey = trim((string) envv('CLOUDINARY_API_KEY', ''));
    $secret = trim((string) envv('CLOUDINARY_API_SECRET', ''));
    if ($cloud === '' || $apiKey === '' || $secret === '' || $publicId === '') {
        return null;
    }

    $encodedPublicId = implode('/', array_map('rawurlencode', explode('/', $publicId)));
    foreach (['authenticated', 'private', 'upload'] as $deliveryType) {
        $url = 'https://api.cloudinary.com/v1_1/' . rawurlencode($cloud)
            . '/resources/video/' . $deliveryType . '/' . $encodedPublicId;
        $body = false;
        $status = 0;

        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERPWD => $apiKey . ':' . $secret,
                CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 10,
            ]);
            $body = curl_exec($curl);
            $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
        } else {
            $context = stream_context_create(['http' => [
                'header' => 'Authorization: Basic ' . base64_encode($apiKey . ':' . $secret),
                'ignore_errors' => true,
                'timeout' => 10,
            ]]);
            $body = @file_get_contents($url, false, $context);
            foreach ($http_response_header ?? [] as $header) {
                if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $matches) === 1) {
                    $status = (int) $matches[1];
                    break;
                }
            }
        }

        if ($status === 200 && is_string($body)) {
            $asset = json_decode($body, true);
            if (is_array($asset)) {
                return [
                    'type' => (string) ($asset['type'] ?? $deliveryType),
                    'format' => strtolower((string) ($asset['format'] ?? 'mp4')),
                    'version' => max(0, (int) ($asset['version'] ?? 0)),
                ];
            }
        }
    }

    return null;
}

function cloudinary_signed_video_url(string $publicId, int $expiresAt): ?string
{
    $cloud = trim((string) envv('CLOUDINARY_CLOUD_NAME', ''));
    $secret = trim((string) envv('CLOUDINARY_API_SECRET', ''));
    $publicId = cloudinary_video_public_id($publicId);
    if ($cloud === '' || $secret === '' || $publicId === '') {
        return null;
    }

    $asset = cloudinary_video_asset_details($publicId);
    $deliveryType = (string) ($asset['type'] ?? 'authenticated');
    $format = preg_replace('/[^a-z0-9]/', '', (string) ($asset['format'] ?? 'mp4')) ?: 'mp4';
    $version = (int) ($asset['version'] ?? 0);

    // Signed delivery URLs use the first eight characters of a
    // URL-safe Base64 SHA digest of everything after the signature component.
    // Asset metadata supplies the actual delivery type, format and version so
    // the generated URL addresses the exact video stored in Cloudinary.
    $deliveryPath = ($version > 0 ? 'v' . $version . '/' : '') . $publicId . '.' . $format;
    $digest = base64_encode(sha1($deliveryPath . $secret, true));
    $signature = substr(rtrim(strtr($digest, '+/', '-_'), '='), 0, 8);
    $encodedPublicId = implode('/', array_map('rawurlencode', explode('/', $publicId)));

    return 'https://res.cloudinary.com/' . rawurlencode($cloud)
        . '/video/' . rawurlencode($deliveryType) . '/s--' . $signature . '--/'
        . ($version > 0 ? 'v' . $version . '/' : '') . $encodedPublicId . '.' . rawurlencode($format);
}

function controller_instructor_video_playback(array $ctx, string $videoId): void
{
    $iv = instructor_video_context($ctx);
    $instructorId = (string) $iv['instructor']['id'];
    $row = db_one('SELECT * FROM instructor_training_videos WHERE id = :id LIMIT 1', ['id' => $videoId]);
    if (!$row) {
        json_response(['message' => 'Video not found.'], 404);
    }
    $subscription = instructor_video_active_subscription($instructorId, (string) $row['program']);
    $video = instructor_video_find_unlocked($instructorId, $videoId, $subscription ? [$subscription] : []);

    $expiresAt = time() + 900;
    $playbackUrl = cloudinary_signed_video_url((string) $row['cloudinary_public_id'], $expiresAt);
    if (!$playbackUrl) {
        json_response(['message' => 'Cloudinary playback is not configured. Set CLOUDINARY_CLOUD_NAME and CLOUDINARY_API_SECRET.'], 503);
    }

    json_response([
        'video' => $video,
        'playbackUrl' => $playbackUrl,
        'expiresAt' => $expiresAt,
        'watermarkIdentity' => [
            'name' => $iv['instructor']['full_name'] ?: $iv['user']['name'],
            'mobile' => $iv['instructor']['mobile'] ?? '',
            'instructorId' => $instructorId,
        ],
    ]);
}

function instructor_video_merge_segments(array $segments): int
{
    usort($segments, fn($a, $b) => $a[0] <=> $b[0]);
    $merged = [];
    foreach ($segments as $segment) {
        [$start, $end] = $segment;
        if ($end <= $start) {
            continue;
        }
        $lastIndex = count($merged) - 1;
        if ($lastIndex >= 0 && $start <= $merged[$lastIndex][1]) {
            $merged[$lastIndex][1] = max($merged[$lastIndex][1], $end);
        } else {
            $merged[] = [$start, $end];
        }
    }
    $total = 0;
    foreach ($merged as $segment) {
        $total += max(0, $segment[1] - $segment[0]);
    }
    return $total;
}

function controller_instructor_video_progress(array $ctx, string $videoId, array $data): void
{
    $iv = instructor_video_context($ctx);
    $instructorId = (string) $iv['instructor']['id'];
    $row = db_one('SELECT program FROM instructor_training_videos WHERE id = :id LIMIT 1', ['id' => $videoId]);
    if (!$row) {
        json_response(['message' => 'Video not found.'], 404);
    }
    $subscription = instructor_video_active_subscription($instructorId, (string) $row['program']);
    $video = instructor_video_find_unlocked($instructorId, $videoId, $subscription ? [$subscription] : []);

    $sessionId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($data['sessionId'] ?? ''));
    if ($sessionId === '') {
        $sessionId = uuid_v4();
    }
    $duration = max(0, (int) ($data['durationSeconds'] ?? $video['durationSeconds']));
    $current = max(0, (int) ($data['currentPositionSeconds'] ?? 0));
    $maxPosition = max(0, (int) ($data['maximumWatchedPositionSeconds'] ?? $current));
    $incomingSegments = is_array($data['segments'] ?? null) ? $data['segments'] : [];
    $now = now_sql();

    foreach ($incomingSegments as $segment) {
        if (!is_array($segment)) {
            continue;
        }
        $start = max(0, (int) floor((float) ($segment['start'] ?? $segment[0] ?? 0)));
        $end = max(0, (int) ceil((float) ($segment['end'] ?? $segment[1] ?? 0)));
        if ($end <= $start || ($maxPosition > 0 && $end > $maxPosition + 3)) {
            continue;
        }
        db_exec_sql(
            'INSERT INTO instructor_video_watch_segments (id, instructor_id, video_id, segment_start, segment_end, session_id, created_at)
             VALUES (:id, :instructor_id, :video_id, :segment_start, :segment_end, :session_id, :created_at)',
            [
                'id' => uuid_v4(),
                'instructor_id' => $instructorId,
                'video_id' => $videoId,
                'segment_start' => $start,
                'segment_end' => $end,
                'session_id' => $sessionId,
                'created_at' => $now,
            ]
        );
    }

    $storedSegments = db_all(
        'SELECT segment_start, segment_end FROM instructor_video_watch_segments WHERE instructor_id = :instructor_id AND video_id = :video_id',
        ['instructor_id' => $instructorId, 'video_id' => $videoId]
    );
    $unique = instructor_video_merge_segments(array_map(fn($s) => [(int) $s['segment_start'], (int) $s['segment_end']], $storedSegments));
    $effectiveDuration = $duration > 0 ? $duration : max(1, (int) $video['durationSeconds']);
    $percentage = min(100, round(($unique / max(1, $effectiveDuration)) * 100, 2));
    $isCompleted = $percentage >= 95;
    $existing = db_one('SELECT * FROM instructor_video_progress WHERE instructor_id = :instructor_id AND video_id = :video_id LIMIT 1', [
        'instructor_id' => $instructorId,
        'video_id' => $videoId,
    ]);

    if ($existing) {
        db_exec_sql(
            'UPDATE instructor_video_progress
             SET subscription_id = :subscription_id,
                 current_position_seconds = :current_position_seconds,
                 maximum_watched_position_seconds = GREATEST(maximum_watched_position_seconds, :maximum_watched_position_seconds),
                 unique_watched_seconds = :unique_watched_seconds,
                 duration_seconds = :duration_seconds,
                 completion_percentage = :completion_percentage,
                 is_completed = GREATEST(is_completed, :is_completed),
                 completed_at = CASE WHEN completed_at IS NULL AND :completed = 1 THEN :completed_at ELSE completed_at END,
                 last_watched_at = :last_watched_at,
                 updated_at = :updated_at
             WHERE instructor_id = :instructor_id AND video_id = :video_id',
            [
                'subscription_id' => $subscription['id'],
                'current_position_seconds' => $current,
                'maximum_watched_position_seconds' => $maxPosition,
                'unique_watched_seconds' => $unique,
                'duration_seconds' => $effectiveDuration,
                'completion_percentage' => $percentage,
                'is_completed' => $isCompleted ? 1 : 0,
                'completed' => $isCompleted ? 1 : 0,
                'completed_at' => $now,
                'last_watched_at' => $now,
                'updated_at' => $now,
                'instructor_id' => $instructorId,
                'video_id' => $videoId,
            ]
        );
    } else {
        db_exec_sql(
            'INSERT INTO instructor_video_progress (
                id, instructor_id, video_id, subscription_id, current_position_seconds,
                maximum_watched_position_seconds, unique_watched_seconds, duration_seconds,
                completion_percentage, is_completed, completed_at, last_watched_at, created_at, updated_at
             ) VALUES (
                :id, :instructor_id, :video_id, :subscription_id, :current_position_seconds,
                :maximum_watched_position_seconds, :unique_watched_seconds, :duration_seconds,
                :completion_percentage, :is_completed, :completed_at, :last_watched_at, :created_at, :updated_at
             )',
            [
                'id' => uuid_v4(),
                'instructor_id' => $instructorId,
                'video_id' => $videoId,
                'subscription_id' => $subscription['id'],
                'current_position_seconds' => $current,
                'maximum_watched_position_seconds' => $maxPosition,
                'unique_watched_seconds' => $unique,
                'duration_seconds' => $effectiveDuration,
                'completion_percentage' => $percentage,
                'is_completed' => $isCompleted ? 1 : 0,
                'completed_at' => $isCompleted ? $now : null,
                'last_watched_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    json_response([
        'progress' => [
            'currentPositionSeconds' => $current,
            'maximumWatchedPositionSeconds' => max($maxPosition, (int) ($existing['maximum_watched_position_seconds'] ?? 0)),
            'uniqueWatchedSeconds' => $unique,
            'durationSeconds' => $effectiveDuration,
            'completionPercentage' => $percentage,
            'isCompleted' => $isCompleted || (int) ($existing['is_completed'] ?? 0) === 1,
        ],
        'library' => instructor_video_library($instructorId, instructor_video_active_subscriptions($instructorId)),
    ]);
}
