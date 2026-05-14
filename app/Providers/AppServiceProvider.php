<?php

namespace App\Providers;

use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;

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
        // =============================================
        // GLOBAL JSON RESPONSE MACROS
        // Now available everywhere: response()->success() and response()->error()
        // =============================================

        Response::macro('success', function ($data = [], string $message = 'Success', int $status = 200) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $data,
            ], $status);
        });

        Response::macro('error', function (string $message = 'An error occurred', int $status = 400, ?array $errors = null) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => $errors,
            ], $status);
        });
    }
}
