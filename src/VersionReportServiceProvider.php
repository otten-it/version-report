<?php

namespace OttenIt\VersionReport;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class VersionReportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/version-report.php', 'version-report');
    }

    public function boot(): void
    {
        $this->publishes([__DIR__.'/../config/version-report.php' => config_path('version-report.php')], 'version-report');

        // Outside the web middleware group: no session, no cookies, no CSRF. Throttled.
        Route::get(config('version-report.path'), VersionReportController::class)
            ->middleware('throttle:10,1')
            ->name('version-report');
    }
}
