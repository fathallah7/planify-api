<?php

namespace App\Http\Controllers;

use App\Http\Requests\Invitation\StoreInvitationRequest;
use App\Http\Resources\InvitationResource;
use App\Models\Invitation;
use App\Services\InvitationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InvitationController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly InvitationService $invitationService
    ) {}

    public function invite(StoreInvitationRequest $request): JsonResponse
    {
        Gate::authorize('create', Invitation::class);

        $invitation = $this->invitationService->invite(
            $request->validated(),
            $request->user()
        );

        return $this->success(
            data: new InvitationResource($invitation),
            message: 'Invitation sent successfully',
            status: 201
        );
    }

    public function accept(string $token): JsonResponse
    {
        $this->invitationService->accept($token);

        return $this->success(message: 'Invitation accepted successfully');
    }
}
