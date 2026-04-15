<?php

namespace App\Http\Middleware;

use App\Models\Student;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->attributes->get('auth_user');
        $student = Student::where('user_id', $user->id)->first();

        if (!$student || !$student->subscription_end) {
            return response()->json(['message' => 'Subscription expired. Please renew.'], 403);
        }

        $isActive = $student->subscription_status === 'active'
            && $student->subscription_end->isFuture();

        if (!$isActive) {
            $student->subscription_status = 'expired';
            $student->save();
            return response()->json(['message' => 'Subscription expired. Please renew.'], 403);
        }

        return $next($request);
    }
}

