<?php

namespace App\Http\Controllers;

use App\Services\PerformanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function __construct(
        private PerformanceService $performanceService
    ) {}

    /**
     * Get leaderboard data
     */
    public function index(Request $request): JsonResponse
    {
        $period = $request->query('period', 'weekly'); // weekly or monthly
        $limit = (int) $request->query('limit', 10);

        $leaderboard = $this->performanceService->getLeaderboard($period, $limit);

        return response()->json([
            'success' => true,
            'data' => $leaderboard,
            'period' => $period,
        ]);
    }

    /**
     * Get top performers (top 3)
     */
    public function topPerformers(Request $request): JsonResponse
    {
        $period = $request->query('period', 'weekly');

        $leaderboard = $this->performanceService->getLeaderboard($period, 3);

        return response()->json([
            'success' => true,
            'data' => $leaderboard,
            'period' => $period,
        ]);
    }

    /**
     * Get student badges
     */
    public function studentBadges(Request $request, string $studentId): JsonResponse
    {
        $badges = \App\Models\AchievementBadge::with('student')
            ->where('student_id', $studentId)
            ->orderBy('awarded_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $badges,
        ]);
    }

    /**
     * Manual override for rankings (admin only)
     */
    public function overrideRanking(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => 'required|uuid|exists:students,id',
            'period' => 'required|in:weekly,monthly',
            'rank' => 'required|integer|min:1',
            'score' => 'required|numeric|min:0',
        ]);

        $studentId = $request->student_id;
        $period = $request->period;
        $rank = $request->rank;
        $score = $request->score;

        if ($period === 'weekly') {
            $weekStart = now()->startOfWeek();
            $weekEnd = now()->endOfWeek();

            \App\Models\WeeklyRanking::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'week_start' => $weekStart,
                    'week_end' => $weekEnd,
                ],
                [
                    'rank' => $rank,
                    'score' => $score,
                    'is_manual_override' => true,
                ]
            );
        } else {
            $monthStart = now()->startOfMonth();
            $monthEnd = now()->endOfMonth();

            \App\Models\MonthlyRanking::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'month_start' => $monthStart,
                    'month_end' => $monthEnd,
                ],
                [
                    'rank' => $rank,
                    'score' => $score,
                    'is_manual_override' => true,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Ranking updated successfully',
        ]);
    }

    /**
     * Get performance history for a student
     */
    public function performanceHistory(Request $request, string $studentId): JsonResponse
    {
        $limit = (int) $request->query('limit', 30);

        $performances = \App\Models\StudentPerformance::where('student_id', $studentId)
            ->orderBy('calculated_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $performances,
        ]);
    }
}