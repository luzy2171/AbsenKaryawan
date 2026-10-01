<?php

namespace App\Providers;

use App\Helpers\CompanyProfile;
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
        Paginator::useBootstrapFive();

        // Identitas perusahaan (nama PT + logo) tersedia di semua view,
        // termasuk template cetak PDF yang tidak lewat middleware auth.
        View::composer('*', function ($view) {
            $view->with('companyProfile', new CompanyProfile);
        });
    }
}
