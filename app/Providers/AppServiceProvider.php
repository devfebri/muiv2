<?php

namespace App\Providers;

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
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
        Carbon::setLocale('id');
        Paginator::defaultView('components.pagination');

        View::composer('*', function ($view): void {
            static $site = null;
            $site ??= Setting::siteProfile();
            $view->with('site', $site);
        });
    }
}
