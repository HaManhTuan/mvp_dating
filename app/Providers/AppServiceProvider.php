<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Không cần gọi Passport::routes() trong Laravel 10+

        // Định nghĩa scopes
        Passport::tokensCan([
            'admin' => 'Admin access',
            'user' => 'User access',
        ]);

        // Thiết lập thời gian hết hạn
        Passport::tokensExpireIn(now()->addHours((int) env('TOKEN_EXPIRY_HOURS', 48)));
        Passport::refreshTokensExpireIn(now()->addDays((int) env('REFRESH_TOKEN_EXPIRY_DAYS', 7)));
        Passport::personalAccessTokensExpireIn(now()->addMonths((int) env('PERSONAL_ACCESS_TOKEN_EXPIRY_MONTHS', 6)));
    }
}
