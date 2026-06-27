<?php

namespace App\Http\Controllers\Api;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\TelegramUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'telegram_id' => ['required', 'integer'],
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'provider' => ['required', 'string', 'in:payme,click,uzum,balance,manual'],
        ]);

        $user = TelegramUser::where('telegram_id', $data['telegram_id'])->firstOrFail();
        $plan = Plan::findOrFail($data['plan_id']);

        $payment = Payment::create([
            'telegram_user_id' => $user->id,
            'plan_id' => $plan->id,
            'amount' => $plan->price,
            'currency' => $plan->currency,
            'provider' => $data['provider'],
            'status' => PaymentStatus::Pending,
            'description' => $plan->name('uz'),
        ]);

        return response()->json([
            'data' => (new PaymentResource($payment->load('plan')))->toArray($request),
            'pay_url' => null,
        ], 201);
    }

    public function show(Request $request, Payment $payment): JsonResponse
    {
        return response()->json([
            'data' => (new PaymentResource($payment->load('plan')))->toArray($request),
        ]);
    }
}
