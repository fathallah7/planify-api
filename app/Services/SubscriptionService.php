<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Plan;
use App\Models\Tenant;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class SubscriptionService
{
  public function __construct()
  {
    Stripe::setApiKey(config('cashier.secret'));
  }

  public function createCheckoutSession(Tenant $tenant, string $planName): string
  {
    $plan = Plan::where('name', $planName)->first();

    if (!$plan || !$plan->stripe_price_id) {
      throw new BusinessException('Plan not found.', 404);
    }

    $session = Session::create([
      'mode'                => 'subscription',
      'payment_method_types' => ['card'],
      'line_items'          => [
        [
          'price'    => $plan->stripe_price_id,
          'quantity' => 1,
        ],
      ],
      'success_url' => config('app.url') . 'https://github.com/fathallah7',
      'cancel_url'  => config('app.url') . 'https://github.com/fathallah7',
      'metadata'    => [
        'tenant_id' => $tenant->id,
        'plan_id'   => $plan->id,
      ],
    ]);

    return $session->url;
  }

  public function cancel(Tenant $tenant): void
  {
    if (!$tenant->subscribed('default')) {
      throw new BusinessException('No active subscription found.');
    }

    $tenant->subscription('default')->cancel();
  }
}
