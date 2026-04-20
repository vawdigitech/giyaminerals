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
        // Set default pagination view
        \Illuminate\Pagination\Paginator::defaultView('pagination::bootstrap-4');
        \Illuminate\Pagination\Paginator::defaultSimpleView('pagination::simple-bootstrap-4');
    }
}
