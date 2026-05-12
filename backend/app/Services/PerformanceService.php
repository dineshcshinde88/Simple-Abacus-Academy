<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentPerformance;
use App\Models\WeeklyRanking;
use App\Models\MonthlyRanking;
use App\Models\AchievementBadge;
use Carbon\Carbon;

class PerformanceService
{
    /**
     * Calculate performance score for a student
     * Weights: Attendance=20%, Test Scores=40%, Worksheet Completion=20%, Accuracy & Speed=20%
     */
    public function calculateStudentScore(Student $student, Carbon $date = null): float
    {
        $date = $date ?: Carbon::now();

        // Get attendance percentage (last 30 days)
        $attendance = $this->calculateAttendance($student, $date);

        // Get average test scores (last 30 days)
        $testScores = $this->calculateTestScores($student, $date);

        // Get worksheet completion percentage (last 30 days)
        $worksheetCompletion = $this->calculateWorksheetCompletion($student, $date);

        // Get accuracy and speed performance (last 30 days)
        $accuracySpeed = $this->calculateAccuracyAndSpeed($student, $date);

        // Calculate weighted score
        $totalScore = ($attendance * 0.20) + ($testScores * 0.40) + ($worksheetCompletion * 0.20) + ($accuracySpeed * 0.20);

        return round($totalScore, 2);
    }

    /**
     * Calculate attendance percentage for the last 30 days
     */
    private function calculateAttendance(Student $student, Carbon $date): float
    {
        // This would need to be implemented based on your attendance tracking system
        // For now, return a placeholder
        return 85.0; // Placeholder - implement based on your attendance data
    }

    /**
     * Calculate average test scores for the last 30 days
     */
    private function calculateTestScores(Student $student, Carbon $date): float
    {
        // This would need to be implemented based on your test scoring system
        // For now, return a placeholder
        return 78.5; // Placeholder - implement based on your test data
    }

    /**
     * Calculate worksheet completion percentage for the last 30 days
     */
    private function calculateWorksheetCompletion(Student $student, Carbon $date): float
    {
        // This would need to be implemented based on your worksheet completion tracking
        // For now, return a placeholder
        return 92.0; // Placeholder - implement based on your worksheet data
    }

    /**
     * Calculate accuracy and speed performance for the last 30 days
     */
    private function calculateAccuracyAndSpeed(Student $student, Carbon $date): float
    {
        // This would need to be implemented based on your accuracy and speed metrics
        // For now, return a placeholder
        return 88.0; // Placeholder - implement based on your accuracy/speed data
    }

    /**
     * Update weekly rankings
     */
    public function updateWeeklyRankings(Carbon $weekStart = null): void
    {
        $weekStart = $weekStart ?: Carbon::now()->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();

        $students = Student::all();
        $rankings = [];

        foreach ($students as $student) {
            $score = $this->calculateStudentScore($student, $weekEnd);
            $rankings[] = [
                'student_id' => $student->id,
                'score' => $score,
            ];
        }

        // Sort by score descending
        usort($rankings, fn($a, $b) => $b['score'] <=> $a['score']);

        // Clear existing rankings for this week
        WeeklyRanking::where('week_start', $weekStart)->delete();

        // Insert new rankings
        foreach ($rankings as $index => $ranking) {
            WeeklyRanking::create([
                'student_id' => $ranking['student_id'],
                'week_start' => $weekStart,
                'week_end' => $weekEnd,
                'rank' => $index + 1,
                'score' => $ranking['score'],
            ]);
        }

        // Award badges for top 3
        $this->awardWeeklyBadges($rankings, $weekStart);
    }

    /**
     * Update monthly rankings
     */
    public function updateMonthlyRankings(Carbon $monthStart = null): void
    {
        $monthStart = $monthStart ?: Carbon::now()->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $students = Student::all();
        $rankings = [];

        foreach ($students as $student) {
            $score = $this->calculateStudentScore($student, $monthEnd);
            $rankings[] = [
                'student_id' => $student->id,
                'score' => $score,
            ];
        }

        // Sort by score descending
        usort($rankings, fn($a, $b) => $b['score'] <=> $a['score']);

        // Clear existing rankings for this month
        MonthlyRanking::where('month_start', $monthStart)->delete();

        // Insert new rankings
        foreach ($rankings as $index => $ranking) {
            MonthlyRanking::create([
                'student_id' => $ranking['student_id'],
                'month_start' => $monthStart,
                'month_end' => $monthEnd,
                'rank' => $index + 1,
                'score' => $ranking['score'],
            ]);
        }

        // Award badges for top 3
        $this->awardMonthlyBadges($rankings, $monthStart);
    }

    /**
     * Award weekly badges to top 3 students
     */
    private function awardWeeklyBadges(array $rankings, Carbon $weekStart): void
    {
        $top3 = array_slice($rankings, 0, 3);

        foreach ($top3 as $index => $ranking) {
            $badgeType = $index === 0 ? 'student_of_month' : 'weekly_top_3';
            $badgeName = $index === 0 ? 'Weekly Champion' : 'Weekly Top 3';

            AchievementBadge::create([
                'student_id' => $ranking['student_id'],
                'badge_type' => $badgeType,
                'badge_name' => $badgeName,
                'description' => "Achieved {$badgeName} for week of {$weekStart->format('M j, Y')}",
                'awarded_at' => Carbon::now(),
            ]);
        }
    }

    /**
     * Award monthly badges to top 3 students
     */
    private function awardMonthlyBadges(array $rankings, Carbon $monthStart): void
    {
        $top3 = array_slice($rankings, 0, 3);

        foreach ($top3 as $index => $ranking) {
            $badgeType = $index === 0 ? 'student_of_month' : 'monthly_top_3';
            $badgeName = $index === 0 ? 'Student of the Month' : 'Monthly Top 3';

            AchievementBadge::create([
                'student_id' => $ranking['student_id'],
                'badge_type' => $badgeType,
                'badge_name' => $badgeName,
                'description' => "Achieved {$badgeName} for {$monthStart->format('F Y')}",
                'awarded_at' => Carbon::now(),
            ]);
        }
    }

    /**
     * Get leaderboard data
     */
    public function getLeaderboard(string $period = 'weekly', int $limit = 10): array
    {
        if ($period === 'weekly') {
            $weekStart = Carbon::now()->startOfWeek();
            $rankings = WeeklyRanking::with('student')
                ->where('week_start', $weekStart)
                ->orderBy('rank')
                ->limit($limit)
                ->get();
        } else {
            $monthStart = Carbon::now()->startOfMonth();
            $rankings = MonthlyRanking::with('student')
                ->where('month_start', $monthStart)
                ->orderBy('rank')
                ->limit($limit)
                ->get();
        }

        return $rankings->map(function ($ranking) {
            return [
                'rank' => $ranking->rank,
                'student' => $ranking->student->name,
                'score' => $ranking->score,
                'student_id' => $ranking->student_id,
            ];
        })->toArray();
    }
}