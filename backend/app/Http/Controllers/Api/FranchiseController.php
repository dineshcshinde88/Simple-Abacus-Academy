<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class FranchiseController extends Controller
{
    public function apply(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string'],
            'email' => ['required', 'email'],
            'mobile' => ['required', 'string'],
            'location' => ['required', 'string'],
            'qualification' => ['required', 'string'],
            'languages' => ['required', 'string'],
            'plan' => ['required', 'string'],
            'message' => ['nullable', 'string'],
        ]);

        $to = env('FRANCHISE_NOTIFICATION_EMAIL', 'simpleabacuspune@gmail.com');
        Mail::raw(
            "Franchise Application\nName: {$data['name']}\nEmail: {$data['email']}\nMobile: {$data['mobile']}\nLocation: {$data['location']}",
            fn ($message) => $message->to($to)->subject('New Franchise Application')
        );

        return response()->json(['message' => 'Application received']);
    }
}

