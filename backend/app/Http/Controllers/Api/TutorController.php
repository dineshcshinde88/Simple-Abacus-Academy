<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Level;
use App\Models\Student;
use App\Models\Tutor;
use App\Models\User;
use App\Models\Video;
use App\Models\Worksheet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TutorController extends Controller
{
    private function currentTutor(Request $request): ?Tutor
    {
        $user = $request->attributes->get('auth_user');
        return Tutor::with('user')->where('user_id', $user->id)->first();
    }

    public function profile(Request $request)
    {
        $tutor = $this->currentTutor($request);
        if (!$tutor) {
            return response()->json(['message' => 'Tutor not found'], 404);
        }
        return response()->json(['tutor' => $tutor]);
    }

    public function students(Request $request)
    {
        $tutor = $this->currentTutor($request);
        if (!$tutor) {
            return response()->json(['message' => 'Tutor not found'], 404);
        }

        $students = Student::with('user')->where('tutor_id', $tutor->id)->get();
        return response()->json(['students' => $students]);
    }

    public function addStudent(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $existing = User::where('email', strtolower($data['email']))->first();
        if ($existing) {
            return response()->json(['message' => 'Email already registered'], 409);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => Hash::make($data['password']),
            'role' => 'student',
        ]);

        $tutor = $this->currentTutor($request);
        if (!$tutor) {
            return response()->json(['message' => 'Tutor not found'], 404);
        }

        $student = Student::create([
            'user_id' => $user->id,
            'tutor_id' => $tutor->id,
        ]);

        return response()->json(['student' => $student], 201);
    }

    public function assignLevel(Request $request, string $studentId)
    {
        $data = $request->validate(['levelId' => ['required', 'uuid']]);
        $level = Level::find($data['levelId']);
        if (!$level) {
            return response()->json(['message' => 'Level not found'], 404);
        }

        $student = Student::find($studentId);
        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }
        $student->level_id = $level->id;
        $student->save();

        return response()->json(['student' => $student]);
    }

    public function uploadVideo(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string'],
            'url' => ['nullable', 'url'],
            'levelId' => ['required', 'uuid'],
            'file' => ['nullable', 'file'],
        ]);

        $tutor = $this->currentTutor($request);
        if (!$tutor) {
            return response()->json(['message' => 'Tutor not found'], 404);
        }

        $fileUrl = null;
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('uploads', 'public');
            $fileUrl = rtrim((string) env('BASE_URL', ''), '/') . '/storage/' . $path;
        }

        $finalUrl = $data['url'] ?? $fileUrl;
        if (!$finalUrl) {
            return response()->json(['message' => 'Video URL or file is required'], 400);
        }

        $video = Video::create([
            'title' => $data['title'],
            'url' => $finalUrl,
            'level_id' => $data['levelId'],
            'uploaded_by' => $tutor->id,
        ]);

        return response()->json(['video' => $video], 201);
    }

    public function uploadWorksheet(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string'],
            'pdfUrl' => ['nullable', 'url'],
            'levelId' => ['required', 'uuid'],
            'file' => ['nullable', 'file'],
        ]);

        $tutor = $this->currentTutor($request);
        if (!$tutor) {
            return response()->json(['message' => 'Tutor not found'], 404);
        }

        $fileUrl = null;
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('uploads', 'public');
            $fileUrl = rtrim((string) env('BASE_URL', ''), '/') . '/storage/' . $path;
        }

        $finalUrl = $data['pdfUrl'] ?? $fileUrl;
        if (!$finalUrl) {
            return response()->json(['message' => 'Worksheet PDF URL or file is required'], 400);
        }

        $worksheet = Worksheet::create([
            'title' => $data['title'],
            'pdf_url' => $finalUrl,
            'level_id' => $data['levelId'],
            'created_by' => $tutor->id,
        ]);

        return response()->json(['worksheet' => $worksheet], 201);
    }
}
