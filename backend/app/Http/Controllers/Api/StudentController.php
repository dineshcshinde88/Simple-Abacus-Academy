<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Progress;
use App\Models\Student;
use App\Models\Video;
use App\Models\VideoHistory;
use App\Models\Worksheet;
use App\Models\WorksheetCompletion;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    private function currentStudent(Request $request): ?Student
    {
        $user = $request->attributes->get('auth_user');
        return Student::with(['user', 'level'])->where('user_id', $user->id)->first();
    }

    public function dashboard(Request $request)
    {
        $student = $this->currentStudent($request);
        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        $status = $student->subscription_status;
        if ($student->subscription_end && $student->subscription_end->isPast()) {
            $status = 'expired';
            if ($student->subscription_status !== 'expired') {
                $student->subscription_status = 'expired';
                $student->save();
            }
        }

        $worksheetsCount = $student->level_id ? Worksheet::where('level_id', $student->level_id)->count() : 0;
        $videosCount = $student->level_id ? Video::where('level_id', $student->level_id)->count() : 0;
        $batches = is_array($student->batches) ? $student->batches : [];

        return response()->json([
            'name' => $student->user?->name ?? 'Student',
            'level' => $student->level?->level_name,
            'batchesCount' => count($batches),
            'worksheetsCount' => $worksheetsCount,
            'videosCount' => $videosCount,
            'subscriptionStatus' => $status,
            'expiryDate' => $student->subscription_end,
        ]);
    }

    public function videos(Request $request)
    {
        $student = $this->currentStudent($request);
        if (!$student || !$student->level_id) {
            return response()->json(['message' => 'Level not assigned'], 404);
        }

        return response()->json(['videos' => Video::where('level_id', $student->level_id)->latest()->get()]);
    }

    public function worksheets(Request $request)
    {
        $student = $this->currentStudent($request);
        if (!$student || !$student->level_id) {
            return response()->json(['message' => 'Level not assigned'], 404);
        }

        return response()->json(['worksheets' => Worksheet::where('level_id', $student->level_id)->latest()->get()]);
    }

    public function upsertProgress(Request $request)
    {
        $student = $this->currentStudent($request);
        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        $data = $request->validate([
            'levelId' => ['nullable', 'uuid'],
            'score' => ['nullable', 'numeric'],
            'completedLessons' => ['nullable', 'integer'],
        ]);

        $levelId = $data['levelId'] ?? $student->level_id;
        if (!$levelId) {
            return response()->json(['message' => 'Level is required for progress tracking'], 400);
        }

        $progress = Progress::updateOrCreate(
            ['student_id' => $student->id, 'level_id' => $levelId],
            [
                'score' => (int) ($data['score'] ?? 0),
                'completed_lessons' => (int) ($data['completedLessons'] ?? 0),
            ]
        );

        return response()->json(['progress' => $progress]);
    }

    public function recordVideoHistory(Request $request)
    {
        $student = $this->currentStudent($request);
        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        $data = $request->validate([
            'videoId' => ['required', 'uuid'],
            'progressPercent' => ['nullable', 'numeric'],
        ]);

        $history = VideoHistory::updateOrCreate(
            ['student_id' => $student->id, 'video_id' => $data['videoId']],
            ['watched_at' => now(), 'progress_percent' => (int) ($data['progressPercent'] ?? 0)]
        );

        return response()->json(['history' => $history], 201);
    }

    public function recordWorksheetCompletion(Request $request)
    {
        $student = $this->currentStudent($request);
        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        $data = $request->validate([
            'worksheetId' => ['required', 'uuid'],
            'score' => ['nullable', 'numeric'],
        ]);

        $completion = WorksheetCompletion::updateOrCreate(
            ['student_id' => $student->id, 'worksheet_id' => $data['worksheetId']],
            ['completed_at' => now(), 'score' => (int) ($data['score'] ?? 0)]
        );

        return response()->json(['completion' => $completion], 201);
    }

    public function progress(Request $request)
    {
        $student = $this->currentStudent($request);
        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }
        return response()->json(['progress' => Progress::where('student_id', $student->id)->get()]);
    }

    public function videoHistory(Request $request)
    {
        $student = $this->currentStudent($request);
        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }
        return response()->json(['history' => VideoHistory::where('student_id', $student->id)->get()]);
    }

    public function worksheetCompletions(Request $request)
    {
        $student = $this->currentStudent($request);
        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }
        return response()->json(['completions' => WorksheetCompletion::where('student_id', $student->id)->get()]);
    }
}

