<?php

namespace App\Providers;

use App\Models\CompanyProfile;
use App\Services\DashboardService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
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
            $user = auth()->user();

            $notifications = ($user && ($user->hasRole('admin') || $user->hasRole('direktur') || $user->hasRole('karyawan')))
                ? $this->cachedActionItems($user)
                : [];

            $view->with('navbarNotifications', $notifications);
        });
    }

    private function cachedActionItems($user): array
    {
        return Cache::remember('navbar_notifications_'.$user->id, 30, function () {
            return app(DashboardService::class)->getActionItems();
        });
    }
}
