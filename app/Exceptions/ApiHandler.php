<?php

namespace App\Exceptions;

use Illuminate\Http\Request;
use Throwable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use App\Traits\ApiResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ApiHandler
{
  use ApiResponse;

  public function __invoke(Throwable $e, Request $request)
  {
    if ($request->is('api/*') || $request->expectsJson()) {
      if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
        return $this->error(message: 'Resource not found', status: 404);
      }

      if ($e instanceof MethodNotAllowedHttpException) {
        return $this->error(message: 'Method not allowed', status: 405);
      }

      if ($e instanceof AuthenticationException) {
        return $this->error(message: 'Unauthenticated', status: 401);
      }

      if ($e instanceof AuthorizationException || $e instanceof AccessDeniedHttpException) {
        $message = $e->getMessage() ?: 'Forbidden';

        if ($message === 'This action is unauthorized.') {
          $message = 'You do not have permission to perform this action.';
        }

        return $this->error(message: $message, status: 403);
      }

      if ($e instanceof ThrottleRequestsException) {
        return $this->error(message: 'Too many requests', status: 429);
      }

      if ($e instanceof ValidationException) {
        return $this->error(
          message: 'Validation failed',
          status: 422,
          errors: $e->errors()
        );
      }

      if ($e instanceof BusinessException) {
        return $this->error(
          message: $e->getMessage(),
          status: $e->getStatusCode(),
        );
      }

      return $this->error(message: 'Server error', status: 500);
    }

    return null;
  }
}
