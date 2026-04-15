<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Level;
use App\Models\Student;
use App\Models\SubscriptionPlan;
use App\Models\Tutor;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function students()
    {
        return response()->json([
            'students' => Student::with(['user', 'level', 'tutor'])->get(),
        ]);
    }

    public function tutors()
    {
        return response()->json([
            'tutors' => Tutor::with(['user', 'students'])->get(),
        ]);
    }

    public function createPlan(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string'],
            'durationDays' => ['required', 'integer'],
            'price' => ['required', 'numeric'],
        ]);

        if (SubscriptionPlan::where('name', $data['name'])->exists()) {
            return response()->json(['message' => 'Plan already exists'], 409);
        }

        $plan = SubscriptionPlan::create([
            'name' => $data['name'],
            'duration_days' => $data['durationDays'],
            'price' => $data['price'],
        ]);

        return response()->json(['plan' => $plan], 201);
    }

    public function assignTutor(Request $request, string $studentId)
    {
        $data = $request->validate(['tutorId' => ['required', 'uuid']]);
        $tutor = Tutor::find($data['tutorId']);
        $student = Student::find($studentId);

        if (!$tutor || !$student) {
            return response()->json(['message' => 'Tutor or student not found'], 404);
        }

        $student->tutor_id = $tutor->id;
        $student->save();

        return response()->json(['student' => $student, 'tutor' => $tutor]);
    }

    public function createLevel(Request $request)
    {
        $data = $request->validate([
            'levelName' => ['required', 'string'],
            'duration' => ['required', 'integer'],
            'description' => ['nullable', 'string'],
        ]);

        $level = Level::create([
            'level_name' => $data['levelName'],
            'duration' => $data['duration'],
            'description' => $data['description'] ?? null,
        ]);

        return response()->json(['level' => $level], 201);
    }

    public function assignSubscription(Request $request, string $studentId)
    {
        $data = $request->validate([
            'planId' => ['nullable', 'uuid'],
            'durationDays' => ['nullable', 'integer'],
            'startDate' => ['nullable', 'date'],
        ]);

        $student = Student::find($studentId);
        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        $plan = null;
        if (!empty($data['planId'])) {
            $plan = SubscriptionPlan::find($data['planId']);
            if (!$plan) {
                return response()->json(['message' => 'Plan not found'], 404);
            }
        }

        $start = !empty($data['startDate']) ? Carbon::parse($data['startDate']) : now();
        $days = $plan ? $plan->duration_days : (int) ($data['durationDays'] ?? 0);
        if ($days <= 0) {
            return response()->json(['message' => 'Duration days is required'], 400);
        }

        $end = (clone $start)->addDays($days);

        $student->subscription_plan = $plan ? $plan->name : 'Custom Plan';
        $student->subscription_start = $start;
        $student->subscription_end = $end;
        $student->subscription_status = 'active';
        $student->save();

        return response()->json([
            'subscription' => [
                'planName' => $student->subscription_plan,
                'startDate' => $student->subscription_start,
                'endDate' => $student->subscription_end,
                'status' => $student->subscription_status,
            ],
        ]);
    }

    public function stats()
    {
        return response()->json([
            'students' => Student::count(),
            'tutors' => Tutor::count(),
            'activeSubscriptions' => Student::where('subscription_status', 'active')->count(),
        ]);
    }
}
