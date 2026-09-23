<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        // 401 Unauthorized
        $this->renderable(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => '認証が必要です。',
                ], 401);
            }
        });

        // 403 Forbidden
        $this->renderable(function (AuthorizationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'この勤怠を操作する権限がありません。',
                ], 403);
            }
        });

        // 404 Not Found
        $this->renderable(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => '勤怠記録が見つかりません。',
                ], 404);
            }
        });

        // 422 Unprocessable Entity
        $this->renderable(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => '入力内容に誤りがあります。',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        $this->reportable(function (Throwable $e) {
            //
        });
    }
}