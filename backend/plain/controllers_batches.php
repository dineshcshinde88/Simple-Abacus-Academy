<?php

function ensure_batch_schema(): void
{
    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS tutor_batches (
            id CHAR(36) PRIMARY KEY,
            tutor_id CHAR(36) NOT NULL,
            name VARCHAR(150) NOT NULL,
            course VARCHAR(80) NOT NULL,
            level VARCHAR(100) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_tutor_batches_tutor (tutor_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS tutor_batch_students (
            batch_id CHAR(36) NOT NULL,
            student_id CHAR(36) NOT NULL,
            assigned_at DATETIME NOT NULL,
            PRIMARY KEY (batch_id, student_id),
            UNIQUE KEY uq_tutor_batch_student (student_id),
            INDEX idx_batch_students_student (student_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS tutor_batch_classes (
            id CHAR(36) PRIMARY KEY,
            batch_id CHAR(36) NOT NULL,
            topic VARCHAR(200) NOT NULL,
            class_date DATE NULL,
            class_time TIME NULL,
            meeting_link VARCHAR(1000) NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_batch_classes_batch (batch_id),
            INDEX idx_batch_classes_date (class_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS tutor_class_attendance (
            class_id CHAR(36) NOT NULL,
            student_id CHAR(36) NOT NULL,
            is_present TINYINT(1) NOT NULL DEFAULT 0,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (class_id, student_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function batch_payload_for_tutor(string $tutorId): array
{
    $batchRows = db_all('SELECT * FROM tutor_batches WHERE tutor_id = :tutor_id ORDER BY created_at DESC', ['tutor_id' => $tutorId]);
    $assignmentRows = db_all(
        'SELECT tbs.batch_id, tbs.student_id FROM tutor_batch_students tbs
         INNER JOIN tutor_batches b ON b.id = tbs.batch_id WHERE b.tutor_id = :tutor_id',
        ['tutor_id' => $tutorId]
    );
    $classRows = db_all(
        'SELECT c.* FROM tutor_batch_classes c INNER JOIN tutor_batches b ON b.id = c.batch_id
         WHERE b.tutor_id = :tutor_id ORDER BY c.class_date ASC, c.class_time ASC',
        ['tutor_id' => $tutorId]
    );
    $attendanceRows = db_all(
        'SELECT a.class_id, a.student_id, a.is_present FROM tutor_class_attendance a
         INNER JOIN tutor_batch_classes c ON c.id = a.class_id
         INNER JOIN tutor_batches b ON b.id = c.batch_id WHERE b.tutor_id = :tutor_id',
        ['tutor_id' => $tutorId]
    );
    $studentIds = [];
    foreach ($assignmentRows as $row) $studentIds[$row['batch_id']][] = $row['student_id'];
    $attendance = [];
    foreach ($attendanceRows as $row) $attendance[$row['class_id']][$row['student_id']] = (bool) $row['is_present'];
    return [
        'batches' => array_map(static fn($row) => [
            'id' => $row['id'], 'name' => $row['name'], 'course' => $row['course'], 'level' => $row['level'],
            'studentIds' => $studentIds[$row['id']] ?? [],
        ], $batchRows),
        'classes' => array_map(static fn($row) => [
            'id' => $row['id'], 'batchId' => $row['batch_id'], 'topic' => $row['topic'],
            'date' => $row['class_date'] ?: '', 'time' => $row['class_time'] ? substr($row['class_time'], 0, 5) : '',
            'meetingLink' => $row['meeting_link'] ?: '', 'attendance' => $attendance[$row['id']] ?? [],
        ], $classRows),
    ];
}

function controller_tutor_batches(array $ctx): void
{
    ensure_batch_schema();
    $tutor = current_tutor($ctx['user']['id']);
    if (!$tutor) json_response(['message' => 'Tutor not found'], 404);
    json_response(batch_payload_for_tutor($tutor['id']));
}

function controller_tutor_batch_create(array $ctx, array $data): void
{
    ensure_batch_schema();
    $tutor = current_tutor($ctx['user']['id']);
    if (!$tutor) json_response(['message' => 'Tutor not found'], 404);
    $name = trim((string) ($data['name'] ?? ''));
    $course = trim((string) ($data['course'] ?? ''));
    $level = trim((string) ($data['level'] ?? ''));
    if ($name === '' || $course === '' || $level === '') json_response(['message' => 'Name, course and level are required'], 422);
    $id = uuid_v4(); $now = now_sql();
    db_exec_sql('INSERT INTO tutor_batches (id,tutor_id,name,course,level,created_at,updated_at) VALUES (:id,:tutor_id,:name,:course,:level,:created_at,:updated_at)', [
        'id'=>$id, 'tutor_id'=>$tutor['id'], 'name'=>$name, 'course'=>$course, 'level'=>$level, 'created_at'=>$now, 'updated_at'=>$now,
    ]);
    json_response(['batch' => ['id'=>$id,'name'=>$name,'course'=>$course,'level'=>$level,'studentIds'=>[]]], 201);
}

function controller_tutor_batch_delete(array $ctx, string $batchId): void
{
    ensure_batch_schema(); $tutor = current_tutor($ctx['user']['id']);
    $batch = $tutor ? db_one('SELECT id FROM tutor_batches WHERE id=:id AND tutor_id=:tutor_id', ['id'=>$batchId,'tutor_id'=>$tutor['id']]) : null;
    if (!$batch) json_response(['message'=>'Batch not found'], 404);
    $classIds = db_all('SELECT id FROM tutor_batch_classes WHERE batch_id=:id', ['id'=>$batchId]);
    foreach ($classIds as $row) db_exec_sql('DELETE FROM tutor_class_attendance WHERE class_id=:id', ['id'=>$row['id']]);
    db_exec_sql('DELETE FROM tutor_batch_classes WHERE batch_id=:id', ['id'=>$batchId]);
    db_exec_sql('DELETE FROM tutor_batch_students WHERE batch_id=:id', ['id'=>$batchId]);
    db_exec_sql('DELETE FROM tutor_batches WHERE id=:id', ['id'=>$batchId]);
    json_response(['message'=>'Batch deleted']);
}

function controller_tutor_batch_assign(array $ctx, string $batchId, array $data): void
{
    ensure_batch_schema(); $tutor = current_tutor($ctx['user']['id']); $studentId = trim((string)($data['studentId'] ?? ''));
    $batch = $tutor ? db_one('SELECT id FROM tutor_batches WHERE id=:id AND tutor_id=:tutor_id', ['id'=>$batchId,'tutor_id'=>$tutor['id']]) : null;
    $student = $tutor ? db_one('SELECT id FROM students WHERE id=:id AND tutor_id=:tutor_id', ['id'=>$studentId,'tutor_id'=>$tutor['id']]) : null;
    if (!$batch || !$student) json_response(['message'=>'Batch or student not found'], 404);
    db_exec_sql('DELETE FROM tutor_batch_students WHERE student_id=:student_id', ['student_id'=>$studentId]);
    db_exec_sql('INSERT INTO tutor_batch_students (batch_id,student_id,assigned_at) VALUES (:batch_id,:student_id,:assigned_at)', ['batch_id'=>$batchId,'student_id'=>$studentId,'assigned_at'=>now_sql()]);
    json_response(['message'=>'Student assigned']);
}

function controller_tutor_class_create(array $ctx, array $data): void
{
    ensure_batch_schema(); $tutor = current_tutor($ctx['user']['id']); $batchId = trim((string)($data['batchId'] ?? ''));
    $batch = $tutor ? db_one('SELECT id FROM tutor_batches WHERE id=:id AND tutor_id=:tutor_id', ['id'=>$batchId,'tutor_id'=>$tutor['id']]) : null;
    $topic = trim((string)($data['topic'] ?? ''));
    if (!$batch || $topic === '') json_response(['message'=>'Valid batch and topic are required'], 422);
    $id=uuid_v4(); $now=now_sql();
    db_exec_sql('INSERT INTO tutor_batch_classes (id,batch_id,topic,class_date,class_time,meeting_link,created_at,updated_at) VALUES (:id,:batch_id,:topic,:class_date,:class_time,:meeting_link,:created_at,:updated_at)', [
        'id'=>$id,'batch_id'=>$batchId,'topic'=>$topic,'class_date'=>($data['date']??'') ?: null,'class_time'=>($data['time']??'') ?: null,'meeting_link'=>trim((string)($data['meetingLink']??'')) ?: null,'created_at'=>$now,'updated_at'=>$now,
    ]);
    json_response(['class'=>['id'=>$id,'batchId'=>$batchId,'topic'=>$topic,'date'=>$data['date']??'','time'=>$data['time']??'','meetingLink'=>$data['meetingLink']??'','attendance'=>(object)[]]], 201);
}

function controller_tutor_attendance_toggle(array $ctx, string $classId, string $studentId): void
{
    ensure_batch_schema(); $tutor=current_tutor($ctx['user']['id']);
    $valid=$tutor ? db_one('SELECT c.id FROM tutor_batch_classes c INNER JOIN tutor_batches b ON b.id=c.batch_id INNER JOIN tutor_batch_students bs ON bs.batch_id=b.id WHERE c.id=:class_id AND bs.student_id=:student_id AND b.tutor_id=:tutor_id', ['class_id'=>$classId,'student_id'=>$studentId,'tutor_id'=>$tutor['id']]) : null;
    if (!$valid) json_response(['message'=>'Class or assigned student not found'], 404);
    $current=db_one('SELECT is_present FROM tutor_class_attendance WHERE class_id=:class_id AND student_id=:student_id', ['class_id'=>$classId,'student_id'=>$studentId]);
    $present=$current ? !(bool)$current['is_present'] : true;
    db_exec_sql('INSERT INTO tutor_class_attendance (class_id,student_id,is_present,updated_at) VALUES (:class_id,:student_id,:is_present,:updated_at) ON DUPLICATE KEY UPDATE is_present=VALUES(is_present),updated_at=VALUES(updated_at)', ['class_id'=>$classId,'student_id'=>$studentId,'is_present'=>$present?1:0,'updated_at'=>now_sql()]);
    json_response(['present'=>$present]);
}

function controller_student_batches(array $ctx): void
{
    ensure_batch_schema(); $student=current_student($ctx['user']['id']);
    if (!$student) json_response(['message'=>'Student not found'], 404);
    $batch=db_one('SELECT b.* FROM tutor_batches b INNER JOIN tutor_batch_students bs ON bs.batch_id=b.id WHERE bs.student_id=:student_id LIMIT 1', ['student_id'=>$student['id']]);
    if (!$batch) json_response(['batches'=>[]]);
    $classes=db_all('SELECT id,topic,class_date,class_time,meeting_link FROM tutor_batch_classes WHERE batch_id=:batch_id ORDER BY class_date ASC,class_time ASC', ['batch_id'=>$batch['id']]);
    json_response(['batches'=>[ ['id'=>$batch['id'],'name'=>$batch['name'],'course'=>$batch['course'],'level'=>$batch['level'],'classes'=>array_map(static fn($row)=>['id'=>$row['id'],'topic'=>$row['topic'],'date'=>$row['class_date'],'time'=>$row['class_time']?substr($row['class_time'],0,5):'','meetingLink'=>$row['meeting_link']?:''], $classes)] ]]);
}
