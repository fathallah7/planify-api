<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
  public function register(array $data): array
  {
    $plan = Plan::where('name', 'free')->first();

    $tenant = Tenant::create([
      'name'    => $data['company_name'],
      'domain'  => $data['domain'],
      'plan_id' => $plan->id,
      'status'  => 'active',
    ]);

    $user = User::create([
      'tenant_id' => $tenant->id,
      'name'      => $data['name'],
      'email'     => $data['email'],
      'password'  => Hash::make($data['password']),
      'role'      => 'owner',
    ]);

    $token = $user->createToken('auth_token')->plainTextToken;

    return [
      'token'  => $token,
      'user'   => $user,
      'tenant' => $tenant->load('plan'),
    ];
  }

  public function login(array $data): ?array
  {
    $user = User::where('email', $data['email'])->first();

    if (!$user || !Hash::check($data['password'], $user->password)) {
      return null;
    }

    $token = $user->createToken('auth_token')->plainTextToken;

    return [
      'token'  => $token,
      'user'   => $user,
      'tenant' => $user->tenant->load('plan'),
    ];
  }

  public function logout(User $user): void
  {
    $user->currentAccessToken()->delete();
  }
}
