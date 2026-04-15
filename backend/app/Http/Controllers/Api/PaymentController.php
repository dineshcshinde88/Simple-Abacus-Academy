<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function webhook(Request $request)
    {
        return response()->json([
            'received' => true,
            'event' => [
                'provider' => env('PAYMENT_PROVIDER', 'stripe'),
                'receivedAt' => now()->toISOString(),
                'payload' => $request->all(),
                'headers' => [
                    'payment-signature' => $request->header('payment-signature'),
                    'stripe-signature' => $request->header('stripe-signature'),
                ],
            ],
        ]);
    }
}

