<?php

function competition_table_has_column(string $table, string $column): bool
{
    $row = db_one(
        'SELECT COUNT(*) AS c FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name',
        ['table_name' => $table, 'column_name' => $column]
    );
    return ((int) ($row['c'] ?? 0)) > 0;
}

function ensure_competition_schema(): void
{
    static $done = false;
    if ($done) return;

    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS categories (
            id CHAR(36) PRIMARY KEY,
            name VARCHAR(180) NOT NULL,
            slug VARCHAR(180) NOT NULL UNIQUE,
            description TEXT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS subcategories (
            id CHAR(36) PRIMARY KEY,
            category_id CHAR(36) NOT NULL,
            name VARCHAR(180) NOT NULL,
            slug VARCHAR(180) NOT NULL,
            description TEXT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY uniq_subcategories_category_slug (category_id, slug),
            INDEX idx_subcategories_category (category_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS competition_users (
            id CHAR(36) PRIMARY KEY,
            name VARCHAR(180) NOT NULL,
            email VARCHAR(180) NOT NULL UNIQUE,
            mobile VARCHAR(30) NULL,
            city VARCHAR(120) NULL,
            school VARCHAR(180) NULL,
            gender VARCHAR(30) NULL,
            date_of_birth DATE NULL,
            maats_category VARCHAR(120) NULL,
            maats_subcategory VARCHAR(120) NULL,
            calculus_grade VARCHAR(120) NULL,
            street_address TEXT NULL,
            state VARCHAR(120) NULL,
            pin_code VARCHAR(20) NULL,
            country VARCHAR(120) NULL,
            password VARCHAR(255) NULL,
            status VARCHAR(20) NOT NULL DEFAULT "pending",
            approved_at DATETIME NULL,
            approved_by CHAR(36) NULL,
            credentials_sent_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_competition_users_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $profileColumns = [
        'gender' => 'VARCHAR(30) NULL',
        'date_of_birth' => 'DATE NULL',
        'maats_category' => 'VARCHAR(120) NULL',
        'maats_subcategory' => 'VARCHAR(120) NULL',
        'calculus_grade' => 'VARCHAR(120) NULL',
        'street_address' => 'TEXT NULL',
        'state' => 'VARCHAR(120) NULL',
        'pin_code' => 'VARCHAR(20) NULL',
        'country' => 'VARCHAR(120) NULL',
    ];
    foreach ($profileColumns as $column => $definition) {
        if (!competition_table_has_column('competition_users', $column)) {
            db_exec_sql("ALTER TABLE competition_users ADD COLUMN {$column} {$definition}");
        }
    }

    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS competitions (
            id CHAR(36) PRIMARY KEY,
            category_id CHAR(36) NULL,
            subcategory_id CHAR(36) NULL,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            banner_image VARCHAR(500) NULL,
            description TEXT NULL,
            duration_minutes INT NOT NULL DEFAULT 30,
            total_questions INT NOT NULL DEFAULT 0,
            negative_marking DECIMAL(6,2) NOT NULL DEFAULT 0,
            prize_details TEXT NULL,
            fee DECIMAL(10,2) NOT NULL DEFAULT 0,
            currency VARCHAR(10) NOT NULL DEFAULT "INR",
            status VARCHAR(20) NOT NULL DEFAULT "upcoming",
            results_published TINYINT(1) NOT NULL DEFAULT 0,
            created_by CHAR(36) NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_competitions_category (category_id, subcategory_id),
            INDEX idx_competitions_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS competition_schedule (
            id CHAR(36) PRIMARY KEY,
            competition_id CHAR(36) NOT NULL UNIQUE,
            competition_date DATE NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            starts_at DATETIME NOT NULL,
            ends_at DATETIME NOT NULL,
            timezone VARCHAR(80) NOT NULL DEFAULT "Asia/Kolkata",
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_competition_schedule_window (starts_at, ends_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS competition_registrations (
            id CHAR(36) PRIMARY KEY,
            competition_user_id CHAR(36) NOT NULL,
            competition_id CHAR(36) NOT NULL,
            payment_status VARCHAR(20) NOT NULL DEFAULT "pending",
            access_status VARCHAR(20) NOT NULL DEFAULT "locked",
            registered_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY uniq_competition_registration (competition_user_id, competition_id),
            INDEX idx_competition_registrations_competition (competition_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS practice_kits (
            id CHAR(36) PRIMARY KEY,
            competition_id CHAR(36) NULL,
            category_id CHAR(36) NULL,
            subcategory_id CHAR(36) NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NULL,
            pdf_url VARCHAR(500) NULL,
            video_url VARCHAR(500) NULL,
            mcq_json LONGTEXT NULL,
            mock_test_json LONGTEXT NULL,
            validity_days INT NOT NULL DEFAULT 90,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_practice_kits_competition (competition_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS practice_kit_access (
            id CHAR(36) PRIMARY KEY,
            competition_user_id CHAR(36) NOT NULL,
            practice_kit_id CHAR(36) NOT NULL,
            competition_id CHAR(36) NULL,
            start_date DATETIME NOT NULL,
            expiry_date DATETIME NOT NULL,
            access_status VARCHAR(20) NOT NULL DEFAULT "active",
            source VARCHAR(50) NOT NULL DEFAULT "purchase",
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY uniq_practice_kit_access_user_kit (competition_user_id, practice_kit_id),
            INDEX idx_practice_kit_access_user (competition_user_id),
            INDEX idx_practice_kit_access_status (access_status, expiry_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS competition_results (
            id CHAR(36) PRIMARY KEY,
            competition_user_id CHAR(36) NOT NULL,
            competition_id CHAR(36) NOT NULL,
            total_marks DECIMAL(8,2) NOT NULL DEFAULT 0,
            marks_obtained DECIMAL(8,2) NOT NULL DEFAULT 0,
            accuracy DECIMAL(5,2) NOT NULL DEFAULT 0,
            completion_time_seconds INT NOT NULL DEFAULT 0,
            rank_position INT NULL,
            status VARCHAR(20) NOT NULL DEFAULT "completed",
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY uniq_competition_result_user_competition (competition_user_id, competition_id),
            INDEX idx_competition_results_rank (competition_id, rank_position)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS competition_payments (
            id CHAR(36) PRIMARY KEY,
            competition_user_id CHAR(36) NOT NULL,
            competition_id CHAR(36) NULL,
            amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            currency VARCHAR(10) NOT NULL DEFAULT "INR",
            provider VARCHAR(50) NOT NULL DEFAULT "razorpay",
            provider_order_id VARCHAR(150) NULL,
            provider_payment_id VARCHAR(150) NULL,
            status VARCHAR(20) NOT NULL DEFAULT "pending",
            paid_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_competition_payments_user (competition_user_id),
            INDEX idx_competition_payments_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    competition_run_automation();
    $done = true;
}

function competition_slug(string $value): string
{
    $slug = strtolower(trim($value));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?: '';
    return trim($slug, '-');
}

function competition_temp_password(): string
{
    return substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789'), 0, 10);
}

function competition_run_automation(): void
{
    $now = now_sql();
    db_exec_sql('UPDATE practice_kit_access SET access_status = "expired", updated_at = :now WHERE access_status = "active" AND expiry_date < :now', ['now' => $now]);
    db_exec_sql(
        'UPDATE competitions c INNER JOIN competition_schedule s ON s.competition_id = c.id
         SET c.status = "live", c.updated_at = :now
         WHERE c.status IN ("upcoming", "scheduled") AND s.starts_at <= :now AND s.ends_at >= :now',
        ['now' => $now]
    );
    db_exec_sql(
        'UPDATE competitions c INNER JOIN competition_schedule s ON s.competition_id = c.id
         SET c.status = "completed", c.updated_at = :now
         WHERE c.status IN ("upcoming", "scheduled", "live") AND s.ends_at < :now',
        ['now' => $now]
    );
}

function competition_generate_ranks(string $competitionId): void
{
    $rows = db_all(
        'SELECT id FROM competition_results
         WHERE competition_id = :competition_id
         ORDER BY marks_obtained DESC, accuracy DESC, completion_time_seconds ASC, created_at ASC',
        ['competition_id' => $competitionId]
    );
    $rank = 1;
    foreach ($rows as $row) {
        db_exec_sql('UPDATE competition_results SET rank_position = :rank, updated_at = :now WHERE id = :id', [
            'rank' => $rank++,
            'now' => now_sql(),
            'id' => $row['id'],
        ]);
    }
}

function controller_competition_register(array $data): void
{
    ensure_competition_schema();
    $name = trim((string) ($data['name'] ?? ''));
    $email = strtolower(trim((string) ($data['email'] ?? '')));
    $mobile = trim((string) ($data['mobile'] ?? ''));
    $city = trim((string) ($data['city'] ?? ''));
    $school = trim((string) ($data['school'] ?? ''));
    $gender = trim((string) ($data['gender'] ?? ''));
    $dateOfBirth = trim((string) ($data['dateOfBirth'] ?? ''));
    $maatsCategory = trim((string) ($data['maatsCategory'] ?? ''));
    $maatsSubcategory = trim((string) ($data['maatsSubcategory'] ?? ''));
    $calculusGrade = trim((string) ($data['calculusGrade'] ?? ''));
    $streetAddress = trim((string) ($data['streetAddress'] ?? ''));
    $state = trim((string) ($data['state'] ?? ''));
    $pinCode = trim((string) ($data['pinCode'] ?? ''));
    $country = trim((string) ($data['country'] ?? 'India'));
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(['message' => 'Name and valid email are required'], 422);
    }
    if (db_one('SELECT id FROM competition_users WHERE email = :email LIMIT 1', ['email' => $email])) {
        json_response(['message' => 'Registration already submitted'], 409);
    }
    $now = now_sql();
    db_exec_sql(
        'INSERT INTO competition_users
         (id, name, email, mobile, city, school, gender, date_of_birth, maats_category, maats_subcategory,
          calculus_grade, street_address, state, pin_code, country, status, created_at, updated_at)
         VALUES
         (:id, :name, :email, :mobile, :city, :school, :gender, :date_of_birth, :maats_category, :maats_subcategory,
          :calculus_grade, :street_address, :state, :pin_code, :country, "pending", :created_at, :updated_at)',
        [
            'id' => uuid_v4(),
            'name' => $name,
            'email' => $email,
            'mobile' => $mobile !== '' ? $mobile : null,
            'city' => $city !== '' ? $city : null,
            'school' => $school !== '' ? $school : null,
            'gender' => $gender !== '' ? $gender : null,
            'date_of_birth' => $dateOfBirth !== '' ? $dateOfBirth : null,
            'maats_category' => $maatsCategory !== '' ? $maatsCategory : null,
            'maats_subcategory' => $maatsSubcategory !== '' ? $maatsSubcategory : null,
            'calculus_grade' => $calculusGrade !== '' ? $calculusGrade : null,
            'street_address' => $streetAddress !== '' ? $streetAddress : null,
            'state' => $state !== '' ? $state : null,
            'pin_code' => $pinCode !== '' ? $pinCode : null,
            'country' => $country !== '' ? $country : null,
            'created_at' => $now,
            'updated_at' => $now,
        ]
    );
    json_response(['message' => 'Registration submitted. Admin approval is required before login.'], 201);
}

function controller_competition_login(array $data): void
{
    ensure_competition_schema();
    $email = strtolower(trim((string) ($data['email'] ?? '')));
    $password = (string) ($data['password'] ?? '');
    $user = db_one('SELECT * FROM competition_users WHERE email = :email LIMIT 1', ['email' => $email]);
    if (!$user || !password_verify($password, (string) ($user['password'] ?? ''))) {
        json_response(['message' => 'Invalid email or password'], 401);
    }
    if (($user['status'] ?? '') !== 'approved') {
        json_response(['message' => 'Your competition registration is waiting for admin approval.'], 403);
    }
    $safe = ['id' => $user['id'], 'name' => $user['name'], 'email' => $user['email'], 'role' => 'competition_student'];
    json_response(['token' => jwt_create($safe), 'user' => $safe]);
}

function require_competition_user(): array
{
    ensure_competition_schema();
    $header = request_header('Authorization');
    if (!str_starts_with((string) $header, 'Bearer ')) {
        json_response(['message' => 'Unauthorized'], 401);
    }
    try {
        $payload = jwt_parse(substr((string) $header, 7));
    } catch (Throwable $e) {
        json_response(['message' => 'Invalid or expired token'], 401);
    }
    $user = db_one('SELECT * FROM competition_users WHERE id = :id LIMIT 1', ['id' => (string) ($payload['id'] ?? '')]);
    if (!$user || ($user['status'] ?? '') !== 'approved') {
        json_response(['message' => 'Competition access denied'], 403);
    }
    return $user;
}

function competition_dashboard_payload(array $user): array
{
    competition_run_automation();
    $userId = (string) $user['id'];
    $upcoming = (int) db_value('SELECT COUNT(*) FROM competitions WHERE status IN ("upcoming", "scheduled", "live")');
    $purchased = (int) db_value('SELECT COUNT(*) FROM competition_registrations WHERE competition_user_id = :id AND payment_status = "paid"', ['id' => $userId]);
    $completed = (int) db_value('SELECT COUNT(*) FROM competition_results WHERE competition_user_id = :id', ['id' => $userId]);
    $avg = (float) db_value('SELECT COALESCE(AVG(accuracy), 0) FROM competition_results WHERE competition_user_id = :id', ['id' => $userId]);
    $kits = db_all(
        'SELECT a.*, k.title, k.description, k.validity_days
         FROM practice_kit_access a
         INNER JOIN practice_kits k ON k.id = a.practice_kit_id
         WHERE a.competition_user_id = :id
         ORDER BY a.created_at DESC',
        ['id' => $userId]
    );
    return [
        'profile' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'school' => $user['school'] ?? null,
            'gender' => $user['gender'] ?? null,
            'dateOfBirth' => $user['date_of_birth'] ?? null,
            'phone' => $user['mobile'] ?? null,
            'maatsCategory' => $user['maats_category'] ?? null,
            'maatsSubcategory' => $user['maats_subcategory'] ?? null,
            'calculusGrade' => $user['calculus_grade'] ?? null,
            'streetAddress' => $user['street_address'] ?? null,
            'city' => $user['city'] ?? null,
            'state' => $user['state'] ?? null,
            'pinCode' => $user['pin_code'] ?? null,
            'country' => $user['country'] ?? null,
        ],
        'summary' => [
            'upcomingCompetitions' => $upcoming,
            'purchasedCompetitions' => $purchased,
            'activeKits' => count(array_filter($kits, static fn(array $k): bool => ($k['access_status'] ?? '') === 'active')),
            'expiredKits' => count(array_filter($kits, static fn(array $k): bool => ($k['access_status'] ?? '') === 'expired')),
            'examsCompleted' => $completed,
            'averageScore' => round($avg, 2),
            'revenue' => 0,
        ],
        'practiceKits' => array_map(static function (array $kit): array {
            $days = max(0, (int) ceil((strtotime((string) $kit['expiry_date']) - time()) / 86400));
            return [
                'id' => $kit['practice_kit_id'],
                'accessId' => $kit['id'],
                'title' => $kit['title'],
                'description' => $kit['description'],
                'startDate' => $kit['start_date'],
                'expiryDate' => $kit['expiry_date'],
                'accessStatus' => $kit['access_status'],
                'remainingDays' => $days,
                'validityDays' => (int) ($kit['validity_days'] ?? 90),
            ];
        }, $kits),
        'upcomingCompetitions' => db_all(
            'SELECT c.*, cat.name AS category_name, sub.name AS subcategory_name, s.starts_at, s.ends_at
             FROM competitions c
             LEFT JOIN categories cat ON cat.id = c.category_id
             LEFT JOIN subcategories sub ON sub.id = c.subcategory_id
             LEFT JOIN competition_schedule s ON s.competition_id = c.id
             WHERE c.status IN ("upcoming", "scheduled", "live")
             ORDER BY s.starts_at ASC
             LIMIT 10'
        ),
        'completedCompetitions' => db_all(
            'SELECT c.*, cat.name AS category_name, sub.name AS subcategory_name, s.starts_at, s.ends_at
             FROM competitions c
             LEFT JOIN categories cat ON cat.id = c.category_id
             LEFT JOIN subcategories sub ON sub.id = c.subcategory_id
             LEFT JOIN competition_schedule s ON s.competition_id = c.id
             WHERE c.status = "completed"
             ORDER BY s.ends_at DESC
             LIMIT 25'
        ),
    ];
}

function controller_competition_student_dashboard(array $user): void
{
    json_response(competition_dashboard_payload($user));
}

function controller_competition_categories(): void
{
    ensure_competition_schema();
    $categories = db_all('SELECT * FROM categories WHERE is_active = 1 ORDER BY name ASC');
    $subcategories = db_all('SELECT * FROM subcategories WHERE is_active = 1 ORDER BY name ASC');
    json_response(['categories' => $categories, 'subcategories' => $subcategories]);
}

function controller_competition_list(): void
{
    ensure_competition_schema();
    competition_run_automation();
    $rows = db_all(
        'SELECT c.*, cat.name AS category_name, sub.name AS subcategory_name, s.starts_at, s.ends_at
         FROM competitions c
         LEFT JOIN categories cat ON cat.id = c.category_id
         LEFT JOIN subcategories sub ON sub.id = c.subcategory_id
         LEFT JOIN competition_schedule s ON s.competition_id = c.id
         WHERE c.status IN ("upcoming", "scheduled", "live", "completed")
         ORDER BY COALESCE(s.starts_at, c.created_at) ASC'
    );
    $upcoming = array_values(array_filter($rows, static fn(array $row): bool => in_array((string) ($row['status'] ?? ''), ['upcoming', 'scheduled', 'live'], true)));
    $completed = array_values(array_filter($rows, static fn(array $row): bool => (string) ($row['status'] ?? '') === 'completed'));
    json_response(['upcomingCompetitions' => $upcoming, 'completedCompetitions' => $completed]);
}

function controller_competition_leaderboard(): void
{
    ensure_competition_schema();
    foreach (db_all('SELECT id FROM competitions WHERE results_published = 1') as $row) {
        competition_generate_ranks((string) $row['id']);
    }
    $competitionId = trim((string) ($_GET['competitionId'] ?? ''));
    $competitions = db_all(
        'SELECT c.id, c.title, cat.name AS categoryName, sub.name AS subcategoryName
         FROM competitions c
         LEFT JOIN categories cat ON cat.id = c.category_id
         LEFT JOIN subcategories sub ON sub.id = c.subcategory_id
         WHERE c.results_published = 1 OR c.status = "completed"
         ORDER BY c.created_at DESC'
    );
    $participants = [];
    if ($competitionId !== '') {
        $participants = db_all(
            'SELECT r.rank_position AS rankPosition, u.name, r.marks_obtained AS marks, r.total_marks AS totalMarks,
                    r.accuracy, r.completion_time_seconds AS completionTimeSeconds
             FROM competition_results r
             INNER JOIN competition_users u ON u.id = r.competition_user_id
             WHERE r.competition_id = :competition_id
             ORDER BY r.rank_position ASC, r.marks_obtained DESC',
            ['competition_id' => $competitionId]
        );
    }
    json_response(['competitions' => $competitions, 'participants' => $participants]);
}

function controller_competition_admin_overview(): void
{
    ensure_competition_schema();
    json_response([
        'summary' => [
            'totalRegistrations' => (int) db_value('SELECT COUNT(*) FROM competition_users'),
            'pendingApprovals' => (int) db_value('SELECT COUNT(*) FROM competition_users WHERE status = "pending"'),
            'activeCompetitions' => (int) db_value('SELECT COUNT(*) FROM competitions WHERE status IN ("upcoming", "scheduled", "live")'),
            'revenue' => (float) db_value('SELECT COALESCE(SUM(amount), 0) FROM competition_payments WHERE status = "success"'),
            'participantsCount' => (int) db_value('SELECT COUNT(*) FROM competition_registrations'),
            'activePracticeKitAccess' => (int) db_value('SELECT COUNT(*) FROM practice_kit_access WHERE access_status = "active"'),
        ],
        'registrations' => db_all('SELECT id, name, email, mobile, city, school, status, created_at FROM competition_users ORDER BY created_at DESC LIMIT 100'),
        'categories' => db_all('SELECT * FROM categories ORDER BY name ASC'),
        'subcategories' => db_all('SELECT s.*, c.name AS category_name FROM subcategories s LEFT JOIN categories c ON c.id = s.category_id ORDER BY s.created_at DESC'),
        'competitions' => db_all(
            'SELECT c.*, cat.name AS category_name, sub.name AS subcategory_name, s.starts_at, s.ends_at
             FROM competitions c
             LEFT JOIN categories cat ON cat.id = c.category_id
             LEFT JOIN subcategories sub ON sub.id = c.subcategory_id
             LEFT JOIN competition_schedule s ON s.competition_id = c.id
             ORDER BY c.created_at DESC'
        ),
    ]);
}

function controller_competition_admin_approve(string $id, array $ctx): void
{
    ensure_competition_schema();
    $user = db_one('SELECT * FROM competition_users WHERE id = :id LIMIT 1', ['id' => $id]);
    if (!$user) json_response(['message' => 'Registration not found'], 404);
    $password = competition_temp_password();
    db_exec_sql(
        'UPDATE competition_users
         SET status = "approved", password = :password, approved_at = :approved_at, approved_by = :approved_by,
             credentials_sent_at = :credentials_sent_at, updated_at = :updated_at
         WHERE id = :id',
        [
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'approved_at' => now_sql(),
            'approved_by' => $ctx['user']['id'] ?? null,
            'credentials_sent_at' => now_sql(),
            'updated_at' => now_sql(),
            'id' => $id,
        ]
    );
    json_response(['message' => 'Student approved. Share these credentials manually.', 'email' => $user['email'], 'temporaryPassword' => $password]);
}

function controller_competition_admin_category(array $data): void
{
    ensure_competition_schema();
    $name = trim((string) ($data['name'] ?? ''));
    if ($name === '') json_response(['message' => 'Category name is required'], 422);
    $id = uuid_v4();
    db_exec_sql('INSERT INTO categories (id, name, slug, created_at, updated_at) VALUES (:id, :name, :slug, :now, :now)', [
        'id' => $id, 'name' => $name, 'slug' => competition_slug($name), 'now' => now_sql(),
    ]);
    json_response(['category' => db_one('SELECT * FROM categories WHERE id = :id', ['id' => $id])], 201);
}

function controller_competition_admin_subcategory(array $data): void
{
    ensure_competition_schema();
    $categoryId = trim((string) ($data['categoryId'] ?? ''));
    $name = trim((string) ($data['name'] ?? ''));
    if ($categoryId === '' || $name === '') json_response(['message' => 'categoryId and name are required'], 422);
    $id = uuid_v4();
    db_exec_sql('INSERT INTO subcategories (id, category_id, name, slug, created_at, updated_at) VALUES (:id, :category_id, :name, :slug, :now, :now)', [
        'id' => $id, 'category_id' => $categoryId, 'name' => $name, 'slug' => competition_slug($name), 'now' => now_sql(),
    ]);
    json_response(['subcategory' => db_one('SELECT * FROM subcategories WHERE id = :id', ['id' => $id])], 201);
}

function controller_competition_admin_create_competition(array $data, array $ctx): void
{
    ensure_competition_schema();
    $title = trim((string) ($data['title'] ?? ''));
    if ($title === '') json_response(['message' => 'Competition title is required'], 422);
    $id = uuid_v4();
    $now = now_sql();
    $slug = competition_slug($title) . '-' . substr(str_replace('-', '', $id), 0, 6);
    db_exec_sql(
        'INSERT INTO competitions
         (id, category_id, subcategory_id, title, slug, description, duration_minutes, total_questions, negative_marking,
          prize_details, fee, currency, status, created_by, created_at, updated_at)
         VALUES
         (:id, :category_id, :subcategory_id, :title, :slug, :description, :duration_minutes, :total_questions, :negative_marking,
          :prize_details, :fee, :currency, "scheduled", :created_by, :created_at, :updated_at)',
        [
            'id' => $id,
            'category_id' => trim((string) ($data['categoryId'] ?? '')) ?: null,
            'subcategory_id' => trim((string) ($data['subcategoryId'] ?? '')) ?: null,
            'title' => $title,
            'slug' => $slug,
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'duration_minutes' => max(1, (int) ($data['durationMinutes'] ?? 30)),
            'total_questions' => max(0, (int) ($data['totalQuestions'] ?? 0)),
            'negative_marking' => (float) ($data['negativeMarking'] ?? 0),
            'prize_details' => trim((string) ($data['prizeDetails'] ?? '')) ?: null,
            'fee' => (float) ($data['fee'] ?? 0),
            'currency' => strtoupper(trim((string) ($data['currency'] ?? 'INR'))) ?: 'INR',
            'created_by' => $ctx['user']['id'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ]
    );
    $date = trim((string) ($data['competitionDate'] ?? gmdate('Y-m-d')));
    $start = trim((string) ($data['startTime'] ?? '10:00:00'));
    $end = trim((string) ($data['endTime'] ?? '11:00:00'));
    db_exec_sql(
        'INSERT INTO competition_schedule (id, competition_id, competition_date, start_time, end_time, starts_at, ends_at, created_at, updated_at)
         VALUES (:id, :competition_id, :competition_date, :start_time, :end_time, :starts_at, :ends_at, :now, :now)',
        [
            'id' => uuid_v4(),
            'competition_id' => $id,
            'competition_date' => $date,
            'start_time' => $start,
            'end_time' => $end,
            'starts_at' => $date . ' ' . $start,
            'ends_at' => $date . ' ' . $end,
            'now' => $now,
        ]
    );
    json_response(['competition' => db_one('SELECT * FROM competitions WHERE id = :id', ['id' => $id])], 201);
}
