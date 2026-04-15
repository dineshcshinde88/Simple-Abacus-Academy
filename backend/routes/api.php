<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DemoController;
use App\Http\Controllers\Api\FranchiseController;
use App\Http\Controllers\Api\InstructorController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\TutorController;
use App\Http\Middleware\AuthenticateJwt;
use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['ok' => true]));

Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::middleware([AuthenticateJwt::class])->get('/me', [AuthController::class, 'me']);
});

Route::prefix('student')->middleware([AuthenticateJwt::class, EnsureRole::class . ':student'])->group(function (): void {
    Route::get('/dashboard', [StudentController::class, 'dashboard']);
    Route::get('/videos', [StudentController::class, 'videos'])->middleware([CheckSubscription::class]);
    Route::get('/worksheets', [StudentController::class, 'worksheets'])->middleware([CheckSubscription::class]);
    Route::get('/progress', [StudentController::class, 'progress']);
    Route::get('/video-history', [StudentController::class, 'videoHistory']);
    Route::get('/worksheet-completions', [StudentController::class, 'worksheetCompletions']);
    Route::post('/progress', [StudentController::class, 'upsertProgress']);
    Route::post('/video-history', [StudentController::class, 'recordVideoHistory']);
    Route::post('/worksheet-completions', [StudentController::class, 'recordWorksheetCompletion']);
});

Route::prefix('tutor')->middleware([AuthenticateJwt::class, EnsureRole::class . ':tutor'])->group(function (): void {
    Route::get('/profile', [TutorController::class, 'profile']);
    Route::get('/students', [TutorController::class, 'students']);
    Route::post('/add-student', [TutorController::class, 'addStudent']);
    Route::put('/assign-level/{studentId}', [TutorController::class, 'assignLevel']);
    Route::post('/upload-video', [TutorController::class, 'uploadVideo']);
    Route::post('/upload-worksheet', [TutorController::class, 'uploadWorksheet']);
});

Route::prefix('admin')->middleware([AuthenticateJwt::class, EnsureRole::class . ':admin'])->group(function (): void {
    Route::get('/students', [AdminController::class, 'students']);
    Route::get('/tutors', [AdminController::class, 'tutors']);
    Route::get('/stats', [AdminController::class, 'stats']);
    Route::post('/plans', [AdminController::class, 'createPlan']);
    Route::put('/assign-tutor/{studentId}', [AdminController::class, 'assignTutor']);
    Route::put('/assign-subscription/{studentId}', [AdminController::class, 'assignSubscription']);
    Route::post('/levels', [AdminController::class, 'createLevel']);
});

Route::post('/demo/book', [DemoController::class, 'book']);
Route::post('/franchise/apply', [FranchiseController::class, 'apply']);
Route::post('/instructor/apply', [InstructorController::class, 'apply']);
Route::post('/payments/webhook', [PaymentController::class, 'webhook']);

