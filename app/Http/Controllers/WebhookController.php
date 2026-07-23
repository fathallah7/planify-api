<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Tenant;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class WebhookController extends Controller
{
  use ApiResponse;

  public function handle(Request $request): JsonResponse
  {
    $payload   = $request->getContent();
    $sigHeader = $request->header('Stripe-Signature');

    try {
      $event = Webhook::constructEvent(
        $payload,
        $sigHeader,
        config('cashier.webhook.secret')
      );
    } catch (SignatureVerificationException $e) {
      return $this->error('Invalid signature', 400);
    }

    match ($event->type) {
      'checkout.session.completed' => $this->handleCheckoutCompleted($event->data->object),
      'customer.subscription.deleted' => $this->handleSubscriptionCancelled($event->data->object),
      default => null,
    };

    return $this->success(message: 'Webhook handled');
  }

  private function handleCheckoutCompleted(object $session): void
  {
    $tenant = Tenant::find($session->metadata->tenant_id);
    $plan   = Plan::find($session->metadata->plan_id);

    if ($tenant && $plan) {
      $tenant->update([
        'plan_id' => $plan->id,
        'status'  => 'active',
      ]);
    }
  }

  private function handleSubscriptionCancelled(object $subscription): void
  {
    $freePlan = Plan::where('name', 'free')->first();
    $tenant   = Tenant::where('stripe_id', $subscription->customer)->first();

    if ($tenant && $freePlan) {
      $tenant->update([
        'plan_id' => $freePlan->id,
        'status'  => 'active',
      ]);
    }
  }
}
