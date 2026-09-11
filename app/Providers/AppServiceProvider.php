<?php

namespace App\Providers;

use App\Contracts\SmsGateway;
use App\Http\ViewComposers\HeaderNotificationComposer;
use App\Http\ViewComposers\MenuComposer;
use App\Models\Project;
use App\Services\GhasedakSmsGateway;
use App\Services\ProjectStageProvisioningService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SmsGateway::class, GhasedakSmsGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production') && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        View::composer('*', MenuComposer::class);
        View::composer('partials.header', HeaderNotificationComposer::class);

        Project::created(static function (Project $project): void {
            app(ProjectStageProvisioningService::class)->ensureForProject($project);
        });
    }
}
