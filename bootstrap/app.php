<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use App\Exceptions\Handler;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Automatically include Accept: application/json header for API routes
        $middleware->api([
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
        
        // Đăng ký middleware auth.json
        $middleware->alias([
            'auth.json' => \App\Http\Middleware\JsonApiAuthenticate::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {    
        // Xử lý lỗi validation
        $exceptions->render(function (ValidationException $exception, $request) {
            if ($request->expectsJson() || str_contains($request->path(), 'api/')) {
                return response()->json([
                    'success' => false,
                    'message' => 'The given data was invalid.',
                    'errors' => $exception->errors(),
                ], 422);
            }
            
            return null; // Fallback to default rendering
        });
        
        // Xử lý lỗi xác thực
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $exception, $request) {
            if ($request->expectsJson() || str_contains($request->path(), 'api/')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Authentication required.',
                    'errors' => ['token' => ['Invalid or missing access token']]
                ], 401);
            }
            
            return null;
        });
        
        // Xử lý các lỗi khác
        $exceptions->render(function (\Throwable $exception, $request) {
            if ($request->expectsJson() || str_contains($request->path(), 'api/')) {
                $status = 500;
                $message = 'Server error';
                
                if ($exception instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                    $status = 404;
                    $message = 'Resource not found';
                }
                
                if ($exception instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
                    $status = 405;
                    $message = 'Method not allowed';
                }
                
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'error' => config('app.debug') ? $exception->getMessage() : null,
                    'trace' => config('app.debug') ? $exception->getTraceAsString() : null
                ], $status);
            }
            
            return null;
        });
    })->create();
