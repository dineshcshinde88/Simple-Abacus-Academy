<?php

if (is_file(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

require_once __DIR__ . '/plain/core.php';
require_once __DIR__ . '/plain/auth.php';
require_once __DIR__ . '/plain/controllers_auth.php';
require_once __DIR__ . '/plain/controllers_student.php';
require_once __DIR__ . '/plain/controllers_tutor.php';
require_once __DIR__ . '/plain/controllers_admin.php';
require_once __DIR__ . '/plain/controllers_misc.php';

load_env_file(__DIR__ . '/.env');
apply_cors_headers();

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$path = normalize_request_path((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$data = request_body_data($method);

if ($method === 'GET' && ($path === '/api/health' || $path === '/up')) {
    json_response(['ok' => true]);
}

if ($method === 'POST' && $path === '/api/auth/register') {
    controller_auth_register($data);
}
if ($method === 'POST' && $path === '/api/auth/login') {
    controller_auth_login($data);
}
if ($method === 'GET' && $path === '/api/auth/me') {
    controller_auth_me(require_auth());
}

if ($method === 'GET' && $path === '/api/student/dashboard') {
    controller_student_dashboard(require_role(['student']));
}
if ($method === 'GET' && $path === '/api/student/videos') {
    $ctx = require_role(['student']);
    require_active_subscription($ctx['user']['id']);
    controller_student_videos($ctx);
}
if ($method === 'GET' && $path === '/api/student/worksheets') {
    $ctx = require_role(['student']);
    require_active_subscription($ctx['user']['id']);
    controller_student_worksheets($ctx);
}
if ($method === 'GET' && $path === '/api/student/progress') {
    controller_student_progress_list(require_role(['student']));
}
if ($method === 'GET' && $path === '/api/student/video-history') {
    controller_student_video_history_list(require_role(['student']));
}
if ($method === 'GET' && $path === '/api/student/worksheet-completions') {
    controller_student_worksheet_completions_list(require_role(['student']));
}
if ($method === 'POST' && $path === '/api/student/progress') {
    controller_student_upsert_progress(require_role(['student']), $data);
}
if ($method === 'POST' && $path === '/api/student/video-history') {
    controller_student_video_history_save(require_role(['student']), $data);
}
if ($method === 'POST' && $path === '/api/student/worksheet-completions') {
    controller_student_worksheet_completion_save(require_role(['student']), $data);
}

if ($method === 'GET' && $path === '/api/tutor/profile') {
    controller_tutor_profile(require_role(['tutor']));
}
if ($method === 'GET' && $path === '/api/tutor/students') {
    controller_tutor_students(require_role(['tutor']));
}
if ($method === 'POST' && $path === '/api/tutor/add-student') {
    controller_tutor_add_student(require_role(['tutor']), $data);
}
if ($method === 'PUT' && preg_match('#^/api/tutor/assign-level/([a-f0-9-]+)$#i', $path, $m)) {
    controller_tutor_assign_level(require_role(['tutor']), $m[1], $data);
}
if ($method === 'POST' && $path === '/api/tutor/upload-video') {
    controller_tutor_upload_video(require_role(['tutor']), $data);
}
if ($method === 'POST' && $path === '/api/tutor/upload-worksheet') {
    controller_tutor_upload_worksheet(require_role(['tutor']), $data);
}

if ($method === 'GET' && $path === '/api/admin/students') {
    require_role(['admin']);
    controller_admin_students();
}
if ($method === 'GET' && $path === '/api/admin/tutors') {
    require_role(['admin']);
    controller_admin_tutors();
}
if ($method === 'GET' && $path === '/api/admin/stats') {
    require_role(['admin']);
    controller_admin_stats();
}
if ($method === 'POST' && $path === '/api/admin/plans') {
    require_role(['admin']);
    controller_admin_create_plan($data);
}
if ($method === 'PUT' && preg_match('#^/api/admin/assign-tutor/([a-f0-9-]+)$#i', $path, $m)) {
    require_role(['admin']);
    controller_admin_assign_tutor($m[1], $data);
}
if ($method === 'PUT' && preg_match('#^/api/admin/assign-subscription/([a-f0-9-]+)$#i', $path, $m)) {
    require_role(['admin']);
    controller_admin_assign_subscription($m[1], $data);
}
if ($method === 'POST' && $path === '/api/admin/levels') {
    require_role(['admin']);
    controller_admin_create_level($data);
}

if ($method === 'POST' && $path === '/api/demo/book') {
    controller_demo_book($data);
}
if ($method === 'POST' && $path === '/api/franchise/apply') {
    controller_franchise_apply($data);
}
if ($method === 'POST' && $path === '/api/instructor/apply') {
    controller_instructor_apply($data);
}
if ($method === 'POST' && $path === '/api/payments/webhook') {
    controller_payments_webhook($data);
}

json_response(['message' => 'Not Found'], 404);

