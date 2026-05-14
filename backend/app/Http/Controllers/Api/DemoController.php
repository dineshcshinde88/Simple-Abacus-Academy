<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

        if ((bool) env('DEMO_SEND_EMAIL_SYNC', false)) {
            try {
                $programsText = empty($data['programs']) ? 'N/A' : implode(', ', $data['programs']);
                $to = env('DEMO_NOTIFICATION_EMAIL', 'simpleabacuspune@gmail.com');

                Mail::raw(
                    "Free Demo Booking\nName: {$data['name']}\nEmail: {$data['email']}\nMobile: {$data['mobile']}\nPrograms: {$programsText}",
                    fn ($message) => $message->to($to)->subject('New Free Demo Request')
                );
            } catch (\Throwable $e) {
                Log::warning('Demo notification failed', ['error' => $e->getMessage()]);
            }
        }

        return response()->json(['message' => 'Demo request received']);
    }
}
