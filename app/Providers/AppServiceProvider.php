<?php

namespace App\Providers;

use App\Models\CompanyProfile;
use App\Services\DashboardService;
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

        View::composer('layouts.shizuefi.navbar', function ($view): void {
            if (auth()->check() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('direktur'))) {
                $notifications = app(DashboardService::class)->getActionItems();
                $view->with('navbarNotifications', $notifications);
            } else {
                $view->with('navbarNotifications', []);
            }
        });
    }
}
