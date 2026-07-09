<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InvitationService
{
  public function invite(array $data, User $inviter): Invitation
  {

    $userExists = User::where('email', $data['email'])->exists();
    if (!$userExists) {
      throw new BusinessException('This user does not have an account yet. Ask them to register first.');
    }

    $tenant = $inviter->tenant;
    $existingUser = User::where('email', $data['email'])
      ->where('tenant_id', $tenant->id)
      ->first();

    if ($existingUser) {
      throw new BusinessException('This email is already a member of your organization.');
    }

    Invitation::where('email', $data['email'])
      ->where('tenant_id', $tenant->id)
      ->delete();

    $invitation = Invitation::create([
      'tenant_id'  => $tenant->id,
      'invited_by' => $inviter->id,
      'email'      => $data['email'],
      'role'       => $data['role'],
      'token'      => Str::uuid(),
      'expires_at' => now()->addDays(7),
    ]);

    Mail::to($data['email'])->send(new InvitationMail($invitation, $tenant));

    return $invitation;
  }

  public function accept(string $token): void
  {
    $invitation = Invitation::where('token', $token)->first();

    if (!$invitation) {
      throw new BusinessException('Invalid invitation token.', 404);
    }

    if (!$invitation->isPending()) {
      throw new BusinessException('This invitation has expired or already been accepted.');
    }

    $user = User::where('email', $invitation->email)->first();

    if (!$user) {
      throw new BusinessException('User not found.');
    }

    $user->update([
      'tenant_id' => $invitation->tenant_id,
      'role'      => $invitation->role,
    ]);

    $invitation->update(['accepted_at' => now()]);
  }
}
