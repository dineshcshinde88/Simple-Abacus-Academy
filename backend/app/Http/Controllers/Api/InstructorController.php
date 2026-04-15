<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class InstructorController extends Controller
{
    public function apply(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string'],
            'email' => ['required', 'email'],
            'mobile' => ['required', 'string'],
            'address' => ['required', 'string'],
            'gender' => ['nullable', 'string'],
            'dob' => ['nullable', 'string'],
            'qualification' => ['nullable', 'string'],
            'programs' => ['nullable', 'array'],
        ]);

        $to = env('INSTRUCTOR_NOTIFICATION_EMAIL', 'simpleabacuspune@gmail.com');
        Mail::raw(
            "Instructor Application\nName: {$data['name']}\nEmail: {$data['email']}\nMobile: {$data['mobile']}",
            fn ($message) => $message->to($to)->subject('New Instructor Registration')
        );

        return response()->json(['message' => 'Application received']);
    }
}

