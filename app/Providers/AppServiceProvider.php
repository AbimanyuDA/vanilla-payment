<?php

namespace App\Providers;

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
        if (!is_dir('/tmp/dompdf-fonts')) {
            mkdir('/tmp/dompdf-fonts', 0755, true);
        }
    }
}
