<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
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
        $this->reportable(function (Throwable $e) {
            //
        });
        
        // Handle authentication exceptions
        $this->renderable(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->expectsJson() || str_contains($request->path(), 'api/')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Authentication required.',
                    'errors' => ['token' => ['Invalid or missing access token']]
                ], 401);
            }
        });
        
        // Handle general exceptions for API requests
        $this->renderable(function (Throwable $e, $request) {
            if ($request->expectsJson() || str_contains($request->path(), 'api/')) {
                $status = 500;
                $message = 'Server error';
                
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                    $status = 404;
                    $message = 'Resource not found';
                }
                
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
                    $status = 405;
                    $message = 'Method not allowed';
                }
                
                if (config('app.debug')) {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ], $status);
                }
                
                return response()->json([
                    'success' => false,
                    'message' => $message
                ], $status);
            }
        });
    }

    /**
     * Convert a validation exception into a JSON response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Validation\ValidationException  $exception
     * @return \Illuminate\Http\JsonResponse
     */
    protected function invalidJson($request, ValidationException $exception)
    {
        return response()->json([
            'message' => 'The given data was invalid.',
            'errors' => $exception->errors(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
