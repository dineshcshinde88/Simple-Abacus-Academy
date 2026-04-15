<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', ['student', 'tutor', 'admin'])->default('student');
            $table->timestamps();
        });

        Schema::create('tutors', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('levels', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('level_name');
            $table->integer('duration');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('students', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('tutor_id')->nullable()->constrained('tutors')->nullOnDelete();
            $table->foreignUuid('level_id')->nullable()->constrained('levels')->nullOnDelete();
            $table->json('batches')->nullable();
            $table->string('subscription_plan')->nullable();
            $table->timestamp('subscription_start')->nullable();
            $table->timestamp('subscription_end')->nullable();
            $table->enum('subscription_status', ['active', 'expired'])->default('expired');
            $table->timestamps();
        });

        Schema::create('videos', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('url');
            $table->foreignUuid('level_id')->constrained('levels')->cascadeOnDelete();
            $table->foreignUuid('uploaded_by')->constrained('tutors')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('worksheets', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('pdf_url');
            $table->foreignUuid('level_id')->constrained('levels')->cascadeOnDelete();
            $table->foreignUuid('created_by')->constrained('tutors')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('subscription_plans', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->integer('duration_days');
            $table->double('price');
            $table->timestamps();
        });

        Schema::create('progress', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUuid('level_id')->nullable()->constrained('levels')->nullOnDelete();
            $table->integer('score')->default(0);
            $table->integer('completed_lessons')->default(0);
            $table->timestamps();
            $table->unique(['student_id', 'level_id']);
        });

        Schema::create('video_history', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUuid('video_id')->constrained('videos')->cascadeOnDelete();
            $table->timestamp('watched_at')->useCurrent();
            $table->integer('progress_percent')->default(0);
            $table->timestamps();
            $table->unique(['student_id', 'video_id']);
        });

        Schema::create('worksheet_completions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUuid('worksheet_id')->constrained('worksheets')->cascadeOnDelete();
            $table->timestamp('completed_at')->useCurrent();
            $table->integer('score')->default(0);
            $table->timestamps();
            $table->unique(['student_id', 'worksheet_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worksheet_completions');
        Schema::dropIfExists('video_history');
        Schema::dropIfExists('progress');
        Schema::dropIfExists('subscription_plans');
        Schema::dropIfExists('worksheets');
        Schema::dropIfExists('videos');
        Schema::dropIfExists('students');
        Schema::dropIfExists('levels');
        Schema::dropIfExists('tutors');
        Schema::dropIfExists('users');
    }
};

