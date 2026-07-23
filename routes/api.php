<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\WebhookController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/register-member', [AuthController::class, 'registerMember']);
    Route::post('/login',    [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::apiResource('projects', ProjectController::class);
    Route::apiResource('projects.tasks', TaskController::class);
    Route::post('/invitations', [InvitationController::class, 'invite']);

    Route::post('/subscribe', [SubscriptionController::class, 'subscribe']);
    Route::post('/subscribe/cancel', [SubscriptionController::class, 'cancel']);
});

Route::get('/plans', [SubscriptionController::class, 'plans']);

Route::get('/invitations/accept/{token}', [InvitationController::class, 'accept']);

Route::post('/webhooks/stripe', [WebhookController::class, 'handle'])
    ->withoutMiddleware([VerifyCsrfToken::class]);
