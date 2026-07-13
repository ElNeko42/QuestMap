<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Photo submissions: capped per user per day (config: questmap.rate_limits).
        RateLimiter::for('submits', function (Request $request) {
            $perDay = (int) config('questmap.rate_limits.submits_per_day');
            $userId = $request->user()?->id ?: $request->ip();

            return Limit::perDay($perDay)
                ->by("submits:{$userId}")
                ->response(fn () => response()->json([
                    'message' => 'Has alcanzado el límite diario de envíos de fotos. Inténtalo mañana.',
                    'error_code' => 'submit_rate_limited',
                ], 429));
        });
    }
}
