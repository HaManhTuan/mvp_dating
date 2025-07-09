<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminTripController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminCreditTransactionController;
use App\Http\Middleware\ForceJsonResponse;

// Force all routes in this file to return JSON responses
Route::middleware(ForceJsonResponse::class)->group(function () {
    Route::prefix('admin')->group(function () {
        // Public routes
        Route::get('/test', function () {
            return response()->json(['message' => 'Admin API OK']);
        });
        Route::post('/login', [AdminAuthController::class, 'login'])
            ->name('admin.login');
            
        // Protected routes - require authentication
        Route::middleware('auth.json:admin-api')->group(function () {
            Route::get('/profile', [AdminAuthController::class, 'profile']);

            // Trips
            Route::prefix('trips')->group(function () {
              Route::get('/', [AdminTripController::class, 'index']);
              Route::patch('/{id}/status', [AdminTripController::class, 'updateStatus']);
              Route::get('/{id}', [AdminTripController::class, 'show']);
            });

            // Users
            Route::prefix('users')->group(function () {
                Route::get('/', [AdminUserController::class, 'index']);
                Route::get('/{id}', [AdminUserController::class, 'show']);
                Route::patch('/{id}/update-status', [AdminUserController::class, 'updateStatus']);
            });

            // Credit Transactions
            Route::prefix('credit-transactions')->group(function () {
                Route::get('/', [AdminCreditTransactionController::class, 'index']);
                Route::get('/{id}', [AdminCreditTransactionController::class, 'show']);
                Route::get('/balances', [AdminCreditTransactionController::class, 'balances']);
                Route::post('/balances/{id}', [AdminCreditTransactionController::class, 'updateBalance']);

            });

            // Withdraw Requests
            Route::prefix('withdraw-requests')->group(function () {
                Route::get('/', [AdminTripController::class, 'withdrawRequests']);
                Route::get('/approve/{id}', [AdminTripController::class, 'approveWithdrawRequest']);
                Route::get('/reject/{id}', [AdminTripController::class, 'rejectWithdrawRequest']);
            });
        });
    });
});
