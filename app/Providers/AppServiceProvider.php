<?php

namespace App\Providers;

use App\Models\User;
use App\Support\Audit;
use App\Support\CurrentHospital;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(CurrentHospital::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(function (Login $event) {
            if ($event->user instanceof User) {
                Audit::record('auth.login', null, [], $event->user);
            }
        });
        Event::listen(function (Logout $event) {
            if ($event->user instanceof User) {
                Audit::record('auth.logout', null, [], $event->user);
            }
        });
    }
}
