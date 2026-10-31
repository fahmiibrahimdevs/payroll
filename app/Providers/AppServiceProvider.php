<?php

namespace App\Providers;

use App\Models\CompanyProfile;
use Illuminate\Database\Eloquent\Model;
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
        Model::preventLazyLoading(! app()->isProduction());
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());

        View::composer('layouts.shizuefi.sidebar', function ($view): void {
            $companyProfile = auth()->check() ? CompanyProfile::getProfile() : null;

            $view->with('sidebarCompanyProfile', $companyProfile);
        });
    }
}
