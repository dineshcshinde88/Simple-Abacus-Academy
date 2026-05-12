<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Student performance metrics table
        Schema::create('student_performances', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->decimal('attendance_percentage', 5, 2)->default(0); // 0-100
            $table->decimal('test_scores', 5, 2)->default(0); // Average test score
            $table->decimal('worksheet_completion', 5, 2)->default(0); // 0-100 percentage
            $table->decimal('accuracy_percentage', 5, 2)->default(0); // 0-100
            $table->decimal('speed_performance', 5, 2)->default(0); // Points for speed
            $table->decimal('homework_completion', 5, 2)->default(0); // 0-100 percentage
            $table->decimal('instructor_rating', 5, 2)->default(0); // 0-5 rating
            $table->decimal('total_score', 8, 2)->default(0); // Calculated total score
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->index(['student_id', 'calculated_at']);
        });

        // Weekly rankings table
        Schema::create('weekly_rankings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->date('week_start');
            $table->date('week_end');
            $table->integer('rank');
            $table->decimal('score', 8, 2);
            $table->boolean('is_manual_override')->default(false);
            $table->timestamps();

            $table->unique(['student_id', 'week_start', 'week_end']);
            $table->index(['week_start', 'week_end', 'rank']);
        });

        // Monthly rankings table
        Schema::create('monthly_rankings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->date('month_start');
            $table->date('month_end');
            $table->integer('rank');
            $table->decimal('score', 8, 2);
            $table->boolean('is_manual_override')->default(false);
            $table->timestamps();

            $table->unique(['student_id', 'month_start', 'month_end']);
            $table->index(['month_start', 'month_end', 'rank']);
        });

        // Achievement badges table
        Schema::create('achievement_badges', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->enum('badge_type', ['weekly_top_3', 'monthly_top_3', 'student_of_month', 'perfect_attendance', 'high_achiever']);
            $table->string('badge_name');
            $table->text('description')->nullable();
            $table->timestamp('awarded_at');
            $table->timestamps();

            $table->index(['student_id', 'badge_type', 'awarded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achievement_badges');
        Schema::dropIfExists('monthly_rankings');
        Schema::dropIfExists('weekly_rankings');
        Schema::dropIfExists('student_performances');
    }
};