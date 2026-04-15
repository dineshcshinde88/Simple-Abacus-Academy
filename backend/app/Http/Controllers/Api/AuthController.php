<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Tutor;
use App\Models\User;
use App\Support\JwtToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', Rule::in(['student', 'tutor'])],
        ]);

        $existing = User::where('email', strtolower($data['email']))->first();
        if ($existing) {
            return response()->json(['message' => 'Email already registered'], 409);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
        ]);

        if ($user->role === 'student') {
            Student::create(['user_id' => $user->id]);
        }

        if ($user->role === 'tutor') {
            Tutor::create(['user_id' => $user->id]);
        }

        $token = JwtToken::create($user->toArray());

        return response()->json([
            'token' => $token,
            'role' => $user->role,
            'name' => $user->name,
            'user' => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'role' => ['nullable', Rule::in(['student', 'tutor', 'admin'])],
        ]);

        $user = User::where('email', strtolower($data['email']))->first();
        if (!$user) {
            return response()->json(['message' => 'Invalid email or password'], 401);
        }

        if (!empty($data['role']) && $user->role !== $data['role']) {
            return response()->json(['message' => 'Invalid role'], 401);
        }

        if (!Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid email or password'], 401);
        }

        $token = JwtToken::create($user->toArray());

        return response()->json([
            'token' => $token,
            'role' => $user->role,
            'name' => $user->name,
            'user' => $user,
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->attributes->get('auth_user');
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }
}

