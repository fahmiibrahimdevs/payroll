<?php

use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\EnsureActiveUser;
use App\Livewire\WorkLocations\Index;
use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\WorkLocation;
use App\Services\WorkLocationService;
use Composer\Autoload\ClassLoader;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

/** @var array<class-string, string> $workLocationClassMap */
$workLocationClassMap = [
    Index::class => __DIR__.'/../app/Livewire/WorkLocations/Index.php',
    Employee::class => __DIR__.'/../app/Models/Employee.php',
    WorkLocation::class => __DIR__.'/../app/Models/WorkLocation.php',
    WorkLocationService::class => __DIR__.'/../app/Services/WorkLocationService.php',
];

foreach (ClassLoader::getRegisteredLoaders() as $loader) {
    $loader->addPsr4('App\\', __DIR__.'/../app', true);
    $loader->addClassMap($workLocationClassMap);
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('model:prune', ['--model' => ActivityLog::class])
            ->dailyAt('02:00')
            ->withoutOverlapping();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->append(AssignRequestId::class);
        $middleware->alias([
            'active' => EnsureActiveUser::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
