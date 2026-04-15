<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class DemoController extends Controller
{
    public function book(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string'],
            'email' => ['required', 'email'],
            'mobile' => ['required', 'string'],
            'gender' => ['nullable', 'string'],
            'motherTongue' => ['nullable', 'string'],
            'dob' => ['nullable', 'string'],
            'programs' => ['nullable', 'array'],
        ]);

        $programsText = empty($data['programs']) ? 'N/A' : implode(', ', $data['programs']);
        $to = env('DEMO_NOTIFICATION_EMAIL', 'simpleabacuspune@gmail.com');

        Mail::raw(
            "Free Demo Booking\nName: {$data['name']}\nEmail: {$data['email']}\nMobile: {$data['mobile']}\nPrograms: {$programsText}",
            fn ($message) => $message->to($to)->subject('New Free Demo Request')
        );

        return response()->json(['message' => 'Demo request received']);
    }
}

