<?php

function controller_tutor_profile(array $ctx): void
{
    $tutor = current_tutor($ctx['user']['id']);
    if (!$tutor) {
        json_response(['message' => 'Tutor not found'], 404);
    }

    $instructor = db_one('SELECT profile_picture FROM instructors WHERE email = :email LIMIT 1', ['email' => $tutor['user_email']]);
    json_response([
        'profile' => [
            'name' => $tutor['user_name'],
            'email' => $tutor['user_email'],
            'avatarUrl' => !empty($instructor['profile_picture']) ? $instructor['profile_picture'] : null,
        ],
        'tutor' => [
            'id' => $tutor['id'],
            'user_id' => $tutor['user_id'],
            'created_at' => $tutor['created_at'],
            'updated_at' => $tutor['updated_at'],
            'user' => [
                'id' => $tutor['user_id'],
                'name' => $tutor['user_name'],
                'email' => $tutor['user_email'],
                'role' => $tutor['user_role'],
            ],
        ],
    ]);
}

function controller_tutor_profile_update(array $ctx, array $data): void
{
    ensure_instructor_auth_schema();
    $tutor = current_tutor($ctx['user']['id']);
    if (!$tutor) {
        json_response(['message' => 'Tutor not found'], 404);
    }

    $name = trim((string) ($data['name'] ?? ''));
    $email = strtolower(trim((string) ($data['email'] ?? '')));
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(['message' => 'A valid name and email are required.'], 422);
    }
    $duplicate = db_one('SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1', [
        'email' => $email,
        'id' => $ctx['user']['id'],
    ]);
    if ($duplicate) {
        json_response(['message' => 'This email is already registered.'], 409);
    }

    $oldEmail = (string) $tutor['user_email'];
    $instructor = db_one('SELECT profile_picture FROM instructors WHERE email = :email LIMIT 1', ['email' => $oldEmail]);
    $uploadedPicture = instructor_handle_profile_picture();
    $profilePicture = $uploadedPicture !== '' ? $uploadedPicture : (string) ($instructor['profile_picture'] ?? '');
    $usersWriteTable = auth_writable_table(['users', 'user', 'User']);
    $userUpdatedColumn = auth_table_column($usersWriteTable, 'updated_at', 'updatedAt');
    $now = now_sql();
    $pdo = db_conn();
    $pdo->beginTransaction();
    try {
        db_exec_sql(
            "UPDATE {$usersWriteTable} SET name = :name, email = :email, {$userUpdatedColumn} = :updated_at WHERE id = :id",
            ['name' => $name, 'email' => $email, 'updated_at' => $now, 'id' => $ctx['user']['id']]
        );
        db_exec_sql(
            'UPDATE instructors SET full_name = :name, email = :email, profile_picture = :profile_picture WHERE email = :old_email',
            ['name' => $name, 'email' => $email, 'profile_picture' => $profilePicture, 'old_email' => $oldEmail]
        );
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('[TutorProfileUpdate] ' . $e->getMessage());
        json_response(['message' => 'Instructor profile could not be saved.'], 500);
    }

    json_response(['profile' => [
        'name' => $name,
        'email' => $email,
        'avatarUrl' => $profilePicture !== '' ? $profilePicture : null,
    ]]);
}

function controller_tutor_students(array $ctx): void
{
    $tutor = current_tutor($ctx['user']['id']);
    if (!$tutor) {
        json_response(['message' => 'Tutor not found'], 404);
    }

    $rows = db_all(
        'SELECT s.*, u.id AS user_id_ref, u.name AS user_name, u.email AS user_email, u.role AS user_role,
                l.level_name AS level_name
         FROM students s
         INNER JOIN users u ON u.id = s.user_id
         LEFT JOIN levels l ON l.id = s.level_id
         WHERE s.tutor_id = :tutor_id
         ORDER BY s.created_at DESC',
        ['tutor_id' => $tutor['id']]
    );

    $students = array_map(static function (array $row): array {
        $student = $row;
        $student['user'] = [
            'id' => $row['user_id_ref'],
            'name' => $row['user_name'],
            'email' => $row['user_email'],
            'role' => $row['user_role'],
        ];
        unset($student['user_id_ref'], $student['user_name'], $student['user_email'], $student['user_role']);
        return $student;
    }, $rows);

    json_response(['students' => $students]);
}

function controller_tutor_add_student(array $ctx, array $data): void
{
    if (function_exists('ensure_student_registration_schema')) {
        try {
            ensure_student_registration_schema();
        } catch (Throwable $e) {
            // Keep student creation available on restricted production databases;
            // optional profile columns are handled dynamically below.
            error_log('[TutorAddStudentSchema] ' . $e->getMessage());
        }
    }
    $tutor = current_tutor($ctx['user']['id']);
    if (!$tutor) {
        json_response(['message' => 'Tutor not found'], 404);
    }

    $name = trim((string) ($data['name'] ?? ''));
    $email = strtolower(trim((string) ($data['email'] ?? '')));
    $password = (string) ($data['password'] ?? '');
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
        json_response(['message' => 'Name, a valid email, and a password of at least 6 characters are required'], 422);
    }

    $course = trim((string) ($data['course'] ?? ''));
    $phone = preg_replace('/\D+/', '', (string) ($data['parentMobile'] ?? $data['phone'] ?? ''));
    $whatsappNumber = preg_replace('/\D+/', '', (string) ($data['whatsappNumber'] ?? ''));
    $gender = strtolower(trim((string) ($data['gender'] ?? '')));
    $dob = trim((string) ($data['dateOfBirth'] ?? $data['dob'] ?? ''));
    $levelName = trim((string) ($data['level'] ?? ''));
    $feesStatus = strtolower(trim((string) ($data['feesStatus'] ?? 'unpaid')));
    if (!in_array($feesStatus, ['paid', 'unpaid'], true)) {
        $feesStatus = 'unpaid';
    }
    $existingUser = db_one('SELECT id, role FROM users WHERE email = :email LIMIT 1', ['email' => $email]);
    if ($existingUser && (string) $existingUser['role'] !== 'student') {
        json_response(['message' => 'This email is already registered with another account type'], 409);
    }

    $levelId = null;
    if ($levelName !== '') {
        $level = db_one('SELECT id FROM levels WHERE LOWER(level_name) = LOWER(:level_name) LIMIT 1', ['level_name' => $levelName]);
        $levelId = $level['id'] ?? null;
    }

    $pdo = db_conn();
    $userId = $existingUser ? (string) $existingUser['id'] : uuid_v4();
    $existingStudent = $existingUser ? db_one('SELECT id, tutor_id FROM students WHERE user_id = :user_id ORDER BY updated_at DESC LIMIT 1', ['user_id' => $userId]) : null;
    if ($existingStudent && !empty($existingStudent['tutor_id']) && (string) $existingStudent['tutor_id'] !== (string) $tutor['id']) {
        json_response(['message' => 'This student is already assigned to another instructor'], 409);
    }
    $studentId = $existingStudent ? (string) $existingStudent['id'] : uuid_v4();
    $now = now_sql();
    $usersWriteTable = auth_writable_table(['users', 'user', 'User']);
    $userCreatedColumn = auth_table_column($usersWriteTable, 'created_at', 'createdAt');
    $userUpdatedColumn = auth_table_column($usersWriteTable, 'updated_at', 'updatedAt');
    $studentsWriteTable = auth_writable_table(['students', 'student', 'Student']);
    $studentUserColumn = auth_table_column($studentsWriteTable, 'user_id', 'userId');
    $studentTutorColumn = auth_table_column($studentsWriteTable, 'tutor_id', 'tutorId');
    $studentLevelColumn = auth_table_column($studentsWriteTable, 'level_id', 'levelId');
    $studentPhoneCountryColumn = auth_table_column($studentsWriteTable, 'phone_country', 'phoneCountry');
    $studentWhatsappColumn = auth_table_has_column($studentsWriteTable, 'whatsapp_number')
        ? 'whatsapp_number'
        : (auth_table_has_column($studentsWriteTable, 'whatsappNumber') ? 'whatsappNumber' : '');
    $studentCreatedColumn = auth_table_column($studentsWriteTable, 'created_at', 'createdAt');
    $studentUpdatedColumn = auth_table_column($studentsWriteTable, 'updated_at', 'updatedAt');
    $studentFeesColumn = auth_table_column($studentsWriteTable, 'fees_status', 'feesStatus');

    // Legacy Student.levelId references the legacy Level table, while the public
    // levels view can contain IDs from the newer levels table. Resolve by name so
    // the foreign key always receives an ID from the table it actually references.
    if ($levelName !== '' && $studentsWriteTable === 'Student') {
        $legacyLevel = db_one(
            'SELECT id FROM `Level` WHERE LOWER(`levelName`) = LOWER(:level_name) LIMIT 1',
            ['level_name' => $levelName]
        );
        if ($legacyLevel) {
            $levelId = (string) $legacyLevel['id'];
        } elseif ($levelId !== null) {
            $modernLevel = db_one(
                'SELECT duration, description FROM levels WHERE id = :id LIMIT 1',
                ['id' => $levelId]
            );
            db_exec_sql(
                'INSERT INTO `Level` (id, `levelName`, duration, description, `createdAt`, `updatedAt`)
                 VALUES (:id, :level_name, :duration, :description, :created_at, :updated_at)',
                [
                    'id' => $levelId,
                    'level_name' => $levelName,
                    'duration' => max(1, (int) ($modernLevel['duration'] ?? 1)),
                    'description' => $modernLevel['description'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    } elseif ($levelName !== '' && $studentsWriteTable === 'student') {
        $legacyLevel = db_one(
            'SELECT id FROM `level` WHERE LOWER(`levelName`) = LOWER(:level_name) LIMIT 1',
            ['level_name' => $levelName]
        );
        $levelId = $legacyLevel['id'] ?? null;
    }

    $pdo->beginTransaction();
    try {
        if (!$existingUser) {
            db_exec_sql(
                "INSERT INTO {$usersWriteTable} (id, name, email, password, role, {$userCreatedColumn}, {$userUpdatedColumn})
                 VALUES (:id, :name, :email, :password, :role, :created_at, :updated_at)",
                ['id'=>$userId,'name'=>$name,'email'=>$email,'password'=>password_hash($password, PASSWORD_BCRYPT),'role'=>'student','created_at'=>$now,'updated_at'=>$now]
            );
        } else {
            db_exec_sql(
                "UPDATE {$usersWriteTable} SET name = :name, password = :password, {$userUpdatedColumn} = :updated_at WHERE id = :id",
                ['name'=>$name,'password'=>password_hash($password, PASSWORD_BCRYPT),'updated_at'=>$now,'id'=>$userId]
            );
        }

        if (!$existingStudent) {
            $whatsappInsertColumn = $studentWhatsappColumn !== '' ? ", {$studentWhatsappColumn}" : '';
            $whatsappInsertValue = $studentWhatsappColumn !== '' ? ', :whatsapp_number' : '';
            $studentInsertParams = ['id'=>$studentId,'user_id'=>$userId,'tutor_id'=>$tutor['id'],'course'=>$course,'phone_country'=>'+91','phone'=>$phone,'gender'=>$gender,'dob'=>$dob !== '' ? $dob : null,'fees_status'=>$feesStatus,'level_id'=>$levelId,'created_at'=>$now,'updated_at'=>$now];
            if ($studentWhatsappColumn !== '') {
                $studentInsertParams['whatsapp_number'] = $whatsappNumber;
            }
            db_exec_sql(
                "INSERT INTO {$studentsWriteTable} (id, {$studentUserColumn}, {$studentTutorColumn}, course, {$studentPhoneCountryColumn}, phone{$whatsappInsertColumn}, gender, dob, {$studentFeesColumn}, {$studentLevelColumn}, {$studentCreatedColumn}, {$studentUpdatedColumn})
                 VALUES (:id,:user_id,:tutor_id,:course,:phone_country,:phone{$whatsappInsertValue},:gender,:dob,:fees_status,:level_id,:created_at,:updated_at)",
                $studentInsertParams
            );
        } else {
            $whatsappUpdateSql = $studentWhatsappColumn !== '' ? ", {$studentWhatsappColumn}=:whatsapp_number" : '';
            $studentUpdateParams = ['tutor_id'=>$tutor['id'],'course'=>$course,'phone_country'=>'+91','phone'=>$phone,'gender'=>$gender,'dob'=>$dob !== '' ? $dob : null,'fees_status'=>$feesStatus,'level_id'=>$levelId,'updated_at'=>$now,'id'=>$studentId];
            if ($studentWhatsappColumn !== '') {
                $studentUpdateParams['whatsapp_number'] = $whatsappNumber;
            }
            db_exec_sql(
                "UPDATE {$studentsWriteTable} SET {$studentTutorColumn}=:tutor_id, course=:course, {$studentPhoneCountryColumn}=:phone_country, phone=:phone{$whatsappUpdateSql}, gender=:gender, dob=:dob, {$studentFeesColumn}=:fees_status, {$studentLevelColumn}=COALESCE(:level_id,{$studentLevelColumn}), {$studentUpdatedColumn}=:updated_at WHERE id=:id",
                $studentUpdateParams
            );
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('[TutorAddStudent] ' . $e->getMessage());
        json_response(['message' => 'Failed to add student'], 500);
    }

    $student = db_one('SELECT s.*, u.name AS user_name, u.email AS user_email FROM students s INNER JOIN users u ON u.id=s.user_id WHERE s.id=:id', ['id'=>$studentId]);
    json_response(['student' => $student], $existingStudent ? 200 : 201);
}

function controller_tutor_assign_level(array $ctx, string $studentId, array $data): void
{
    $tutor = current_tutor($ctx['user']['id']);
    if (!$tutor) {
        json_response(['message' => 'Tutor not found'], 404);
    }

    $levelId = trim((string) ($data['levelId'] ?? ''));
    if ($levelId === '') {
        json_response(['message' => 'levelId is required'], 422);
    }

    $level = db_one('SELECT id FROM levels WHERE id = :id', ['id' => $levelId]);
    if (!$level) {
        json_response(['message' => 'Level not found'], 404);
    }

    $student = db_one('SELECT id FROM students WHERE id = :id', ['id' => $studentId]);
    if (!$student) {
        json_response(['message' => 'Student not found'], 404);
    }

    db_exec_sql('UPDATE students SET level_id = :level_id, updated_at = :updated_at WHERE id = :id', [
        'level_id' => $levelId,
        'updated_at' => now_sql(),
        'id' => $studentId,
    ]);

    $updated = db_one('SELECT * FROM students WHERE id = :id', ['id' => $studentId]);
    json_response(['student' => $updated]);
}

function controller_tutor_upload_video(array $ctx, array $data): void
{
    $tutor = current_tutor($ctx['user']['id']);
    if (!$tutor) {
        json_response(['message' => 'Tutor not found'], 404);
    }

    $title = trim((string) ($data['title'] ?? ''));
    $levelId = trim((string) ($data['levelId'] ?? ''));
    $url = trim((string) ($data['url'] ?? ''));
    if ($title === '' || $levelId === '') {
        json_response(['message' => 'title and levelId are required'], 422);
    }

    $uploaded = handle_upload_file('file');
    $finalUrl = $url !== '' ? $url : $uploaded;
    if ($finalUrl === '') {
        json_response(['message' => 'Video URL or file is required'], 400);
    }

    $id = uuid_v4();
    $now = now_sql();
    db_exec_sql(
        'INSERT INTO videos (id, title, url, level_id, uploaded_by, created_at, updated_at)
         VALUES (:id, :title, :url, :level_id, :uploaded_by, :created_at, :updated_at)',
        [
            'id' => $id,
            'title' => $title,
            'url' => $finalUrl,
            'level_id' => $levelId,
            'uploaded_by' => $tutor['id'],
            'created_at' => $now,
            'updated_at' => $now,
        ]
    );

    $video = db_one('SELECT * FROM videos WHERE id = :id', ['id' => $id]);
    json_response(['video' => $video], 201);
}

function controller_tutor_upload_worksheet(array $ctx, array $data): void
{
    $tutor = current_tutor($ctx['user']['id']);
    if (!$tutor) {
        json_response(['message' => 'Tutor not found'], 404);
    }

    $title = trim((string) ($data['title'] ?? ''));
    $levelId = trim((string) ($data['levelId'] ?? ''));
    $pdfUrl = trim((string) ($data['pdfUrl'] ?? ''));
    if ($title === '' || $levelId === '') {
        json_response(['message' => 'title and levelId are required'], 422);
    }

    $uploaded = handle_upload_file('file');
    $finalUrl = $pdfUrl !== '' ? $pdfUrl : $uploaded;
    if ($finalUrl === '') {
        json_response(['message' => 'Worksheet PDF URL or file is required'], 400);
    }

    $id = uuid_v4();
    $now = now_sql();
    db_exec_sql(
        'INSERT INTO worksheets (id, title, pdf_url, level_id, created_by, created_at, updated_at)
         VALUES (:id, :title, :pdf_url, :level_id, :created_by, :created_at, :updated_at)',
        [
            'id' => $id,
            'title' => $title,
            'pdf_url' => $finalUrl,
            'level_id' => $levelId,
            'created_by' => $tutor['id'],
            'created_at' => $now,
            'updated_at' => $now,
        ]
    );

    $worksheet = db_one('SELECT * FROM worksheets WHERE id = :id', ['id' => $id]);
    json_response(['worksheet' => $worksheet], 201);
}
