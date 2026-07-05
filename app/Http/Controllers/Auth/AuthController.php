<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\AuthResource;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function register(RegisterRequest $request): AuthResource|JsonResponse
    {
        $result = $this->authService->register($request->validated());
        return $this->success(data: new AuthResource($result), message: 'Registered successfully');
    }

    public function login(LoginRequest $request): AuthResource|JsonResponse
    {
        $result = $this->authService->login($request->validated());

        if (!$result) {
            return $this->error(message: 'Invalid credentials', status: 401);
        }

        return $this->success(data: new AuthResource($result), message: 'Logged in successfully');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());
        return $this->success(message: 'Logged out successfully');
    }
}
