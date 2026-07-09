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
require_once __DIR__ . '/plain/controllers_subscriptions.php';
require_once __DIR__ . '/plain/controllers_instructor_auth.php';
require_once __DIR__ . '/plain/controllers_worksheet_sub.php';
require_once __DIR__ . '/plain/worksheet_docx_importer.php';
require_once __DIR__ . '/plain/controllers_training.php';
require_once __DIR__ . '/plain/controllers_practice.php';
require_once __DIR__ . '/plain/controllers_competition.php';

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

if ($method === 'GET' && $path === '/api/teachers/public') {
    controller_public_teachers();
}

if ($method === 'GET' && $path === '/api/subscriptions/plans/public') {
    controller_public_subscription_plans();
}
if ($method === 'POST' && $path === '/api/competition/register') {
    controller_competition_register($data);
}
if ($method === 'POST' && $path === '/api/competition/login') {
    controller_competition_login($data);
}
if ($method === 'POST' && $path === '/api/competition/forgot-password') {
    controller_competition_forgot_password($data);
}
if ($method === 'POST' && $path === '/api/competition/reset-password') {
    controller_competition_reset_password($data);
}
if ($method === 'GET' && $path === '/api/competition/categories') {
    controller_competition_categories();
}
if ($method === 'GET' && $path === '/api/competition/list') {
    controller_competition_list();
}
if ($method === 'GET' && $path === '/api/competition/leaderboard') {
    controller_competition_leaderboard();
}
if ($method === 'GET' && $path === '/api/competition/student/dashboard') {
    controller_competition_student_dashboard(require_competition_user());
}

if ($method === 'POST' && $path === '/api/training/auth/register') {
    controller_training_register($data);
}
if ($method === 'POST' && $path === '/api/training/auth/login') {
    controller_training_login($data);
}
if ($method === 'GET' && $path === '/api/training/auth/me') {
    controller_training_me(require_auth());
}
if ($method === 'GET' && $path === '/api/training/teacher/dashboard') {
    controller_training_teacher_dashboard(require_training_teacher());
}
if ($method === 'POST' && $path === '/api/training/teacher/students') {
    controller_training_teacher_add_student(require_training_teacher(), $data);
}
if ($method === 'GET' && $path === '/api/training/teacher/shop/orders') {
    controller_training_teacher_shop_orders(require_training_teacher());
}
if ($method === 'POST' && $path === '/api/training/teacher/shop/orders') {
    controller_training_teacher_shop_create_order(require_training_teacher(), $data);
}
if ($method === 'POST' && preg_match('#^/api/training/teacher/shop/orders/([^/]+)/pay$#', $path, $m)) {
    controller_training_teacher_shop_pay_order(require_training_teacher(), $m[1]);
}
if ($method === 'POST' && preg_match('#^/api/training/teacher/shop/orders/([^/]+)/verify$#', $path, $m)) {
    controller_training_teacher_shop_verify_order(require_training_teacher(), $m[1], $data);
}
if ($method === 'GET' && $path === '/api/training/admin/teachers') {
    require_role(['admin']);
    controller_training_admin_teachers();
}
if ($method === 'PUT' && preg_match('#^/api/training/admin/teachers/([^/]+)/approve$#', $path, $m)) {
    require_role(['admin']);
    controller_training_admin_approve_teacher($m[1]);
}
if ($method === 'GET' && $path === '/api/training/admin/students') {
    require_role(['admin']);
    controller_training_admin_students();
}

if ($method === 'POST' && $path === '/api/auth/register') {
    controller_auth_register($data);
}
if ($method === 'POST' && $path === '/api/auth/login') {
    controller_auth_login($data);
}
if ($method === 'POST' && $path === '/api/auth/forgot-password') {
    controller_auth_forgot_password($data);
}
if ($method === 'POST' && $path === '/api/auth/reset-password') {
    controller_auth_reset_password($data);
}
if ($method === 'GET' && $path === '/api/auth/me') {
    controller_auth_me(require_auth());
}
if ($method === 'POST' && $path === '/api/auth/change-password') {
    controller_auth_change_password(require_auth(), $data);
}

if ($method === 'GET' && $path === '/api/student/dashboard') {
    controller_student_dashboard(require_role(['student']));
}
if ($method === 'GET' && $path === '/api/student/profile') {
    controller_student_profile(require_role(['student']));
}
if ($method === 'GET' && $path === '/api/student/subscriptions/plans') {
    controller_student_subscription_plans(require_role(['student']));
}
if ($method === 'GET' && $path === '/api/student/subscriptions/summary') {
    controller_student_subscription_summary(require_role(['student']));
}
if ($method === 'GET' && $path === '/api/student/courses') {
    controller_student_courses(require_role(['student']));
}
if ($method === 'POST' && $path === '/api/student/subscriptions/ensure-plan') {
    controller_student_ensure_worksheet_plan(require_role(['student']), $data);
}
if ($method === 'POST' && $path === '/api/student/subscriptions/create-order') {
    controller_student_create_razorpay_order(require_role(['student']), $data);
}
if ($method === 'POST' && $path === '/api/student/subscriptions/verify') {
    controller_student_verify_razorpay_payment(require_role(['student']), $data);
}
if ($method === 'GET' && $path === '/api/student/worksheets') {
    $ctx = require_role(['student']);
    require_active_subscription($ctx['user']['id']);
    controller_student_worksheets($ctx);
}
if ($method === 'GET' && $path === '/api/student/worksheet-sub') {
    $ctx = require_role(['student']);
    require_active_subscription($ctx['user']['id']);
    controller_student_worksheet_sub_dashboard($ctx);
}
if ($method === 'GET' && preg_match('#^/api/student/worksheet-sub/topics/([^/]+)/questions$#', $path, $m)) {
    $ctx = require_role(['student']);
    require_active_subscription($ctx['user']['id']);
    controller_student_worksheet_sub_questions($ctx, $m[1]);
}
if ($method === 'GET' && preg_match('#^/api/student/worksheet-sub/topics/([^/]+)/practices$#', $path, $m)) {
    $ctx = require_role(['student']);
    require_active_subscription($ctx['user']['id']);
    controller_student_worksheet_sub_practices($ctx, $m[1]);
}
if ($method === 'POST' && $path === '/api/student/worksheet-sub/practices') {
    $ctx = require_role(['student']);
    require_active_subscription($ctx['user']['id']);
    controller_student_worksheet_sub_save_practice($ctx, $data);
}
if ($method === 'GET' && $path === '/api/subscriptions/me') {
    controller_student_subscriptions_me(require_role(['student']));
}
if ($method === 'GET' && $path === '/api/worksheets') {
    controller_student_worksheets_list(require_role(['student']));
}
if ($method === 'GET' && preg_match('#^/api/worksheets/([a-f0-9-]+)/download$#i', $path, $m)) {
    controller_student_worksheet_download(require_role(['student']), $m[1]);
}
if ($method === 'GET' && $path === '/api/student/progress') {
    controller_student_progress_list(require_role(['student']));
}
if ($method === 'GET' && $path === '/api/student/worksheet-completions') {
    controller_student_worksheet_completions_list(require_role(['student']));
}
if ($method === 'POST' && $path === '/api/student/progress') {
    controller_student_upsert_progress(require_role(['student']), $data);
}
if ($method === 'POST' && $path === '/api/student/worksheet-completions') {
    controller_student_worksheet_completion_save(require_role(['student']), $data);
}
if ($method === 'GET' && $path === '/api/student/practice/levels') {
    controller_student_practice_levels(require_role(['student']));
}
if ($method === 'GET' && preg_match('#^/api/student/practice/papers/([^/]+)$#', $path, $m)) {
    controller_student_practice_paper(require_role(['student']), $m[1]);
}
if ($method === 'POST' && $path === '/api/student/practice/progress') {
    controller_student_practice_save_progress(require_role(['student']), $data);
}
if ($method === 'POST' && $path === '/api/student/practice/submit') {
    controller_student_practice_submit(require_role(['student']), $data);
}
if ($method === 'GET' && preg_match('#^/api/student/practice/results/([^/]+)$#', $path, $m)) {
    controller_student_practice_result(require_role(['student']), $m[1]);
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
if ($method === 'GET' && $path === '/api/admin/courses') {
    require_role(['admin']);
    controller_admin_courses_list();
}
if ($method === 'POST' && $path === '/api/admin/courses') {
    require_role(['admin']);
    controller_admin_create_course($data);
}
if ($method === 'PUT' && preg_match('#^/api/admin/courses/([a-f0-9-]+)$#i', $path, $m)) {
    require_role(['admin']);
    controller_admin_update_course($m[1], $data);
}
if ($method === 'DELETE' && preg_match('#^/api/admin/courses/([a-f0-9-]+)$#i', $path, $m)) {
    require_role(['admin']);
    controller_admin_delete_course($m[1]);
}
if ($method === 'POST' && $path === '/api/admin/plans') {
    require_role(['admin']);
    controller_admin_create_plan($data);
}
if ($method === 'GET' && $path === '/api/admin/subscriptions') {
    require_role(['admin']);
    controller_admin_subscriptions_list();
}
if ($method === 'GET' && $path === '/api/admin/payment-audit-logs') {
    require_role(['admin']);
    controller_admin_payment_audit_logs();
}
if ($method === 'POST' && $path === '/api/admin/levels') {
    require_role(['admin']);
    controller_admin_create_level($data);
}

if ($method === 'GET' && $path === '/api/admin/worksheet-sub/reports') {
    require_role(['admin']);
    controller_admin_worksheet_sub_reports();
}
if ($method === 'PUT' && preg_match('#^/api/admin/levels/([a-f0-9-]+)$#i', $path, $m)) {
    require_role(['admin']);
    controller_admin_update_level($m[1], $data);
}
if ($method === 'DELETE' && preg_match('#^/api/admin/levels/([a-f0-9-]+)$#i', $path, $m)) {
    require_role(['admin']);
    controller_admin_delete_level($m[1]);
}
if ($method === 'GET' && $path === '/api/admin/worksheets') {
    require_role(['admin']);
    controller_admin_worksheets_list();
}

if ($method === 'GET' && $path === '/api/admin/payment-config') {
    controller_admin_get_payment_config(require_role(['admin']));
}
if ($method === 'PUT' && $path === '/api/admin/payment-config') {
    controller_admin_set_payment_config(require_role(['admin']), $data);
}
if ($method === 'PUT' && preg_match('#^/api/admin/assign-tutor/([a-f0-9-]+)$#i', $path, $m)) {
    require_role(['admin']);
    controller_admin_assign_tutor($m[1], $data);
}
if ($method === 'PUT' && preg_match('#^/api/admin/assign-subscription/([a-f0-9-]+)$#i', $path, $m)) {
    require_role(['admin']);
    controller_admin_assign_subscription($m[1], $data);
}
if ($method === 'PUT' && preg_match('#^/api/admin/subscriptions/([a-f0-9-]+)$#i', $path, $m)) {
    require_role(['admin']);
    controller_admin_update_subscription($m[1], $data);
}
if ($method === 'GET' && $path === '/api/admin/practice/overview') {
    require_role(['admin']);
    controller_admin_practice_overview();
}
if ($method === 'POST' && $path === '/api/admin/practice/import-defaults') {
    require_role(['admin']);
    controller_admin_practice_import_defaults();
}
if ($method === 'POST' && $path === '/api/admin/practice/upload-docx') {
    require_role(['admin']);
    controller_admin_practice_upload_docx();
}
if ($method === 'PUT' && preg_match('#^/api/admin/practice/levels/([^/]+)$#', $path, $m)) {
    require_role(['admin']);
    controller_admin_practice_update_level($m[1], $data);
}
if ($method === 'GET' && $path === '/api/admin/competition/overview') {
    require_role(['admin']);
    controller_competition_admin_overview();
}
if ($method === 'PUT' && preg_match('#^/api/admin/competition/registrations/([^/]+)/approve$#', $path, $m)) {
    $ctx = require_role(['admin']);
    controller_competition_admin_approve($m[1], $ctx);
}
if ($method === 'POST' && $path === '/api/admin/competition/categories') {
    require_role(['admin']);
    controller_competition_admin_category($data);
}
if ($method === 'POST' && $path === '/api/admin/competition/subcategories') {
    require_role(['admin']);
    controller_competition_admin_subcategory($data);
}
if ($method === 'POST' && $path === '/api/admin/competition/competitions') {
    $ctx = require_role(['admin']);
    controller_competition_admin_create_competition($data, $ctx);
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
if ($method === 'POST' && $path === '/api/instructor/register/start') {
    controller_instructor_register_start($data);
}
if ($method === 'POST' && $path === '/api/instructor/forgot-password') {
    controller_instructor_forgot_password($data);
}
if ($method === 'POST' && $path === '/api/instructor/reset-password') {
    controller_instructor_reset_password($data);
}
if ($method === 'POST' && $path === '/api/payments/webhook') {
    controller_payments_webhook($data);
}
if (($method === 'POST' || $method === 'GET') && $path === '/api/internal/subscriptions/run-reminders') {
    controller_run_subscription_reminders();
}

json_response(['message' => 'Not Found'], 404);
