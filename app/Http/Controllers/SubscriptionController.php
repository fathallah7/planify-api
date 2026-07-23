<?php

namespace App\Http\Controllers;

use App\Http\Requests\Subscription\SubscribeRequest;
use App\Models\Plan;
use App\Services\SubscriptionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly SubscriptionService $subscriptionService
    ) {}

    public function plans(): JsonResponse
    {
        $plans = Plan::all();
        return $this->success(data: $plans, message: 'Plans retrieved successfully');
    }

    public function subscribe(SubscribeRequest $request): JsonResponse
    {
        $url = $this->subscriptionService->createCheckoutSession(
            $request->user()->tenant,
            $request->plan
        );

        return $this->success(
            data: ['checkout_url' => $url],
            message: 'Checkout session created'
        );
    }

    public function cancel(Request $request): JsonResponse
    {
        $this->subscriptionService->cancel($request->user()->tenant);
        return $this->success(message: 'Subscription cancelled successfully');
    }
}
