<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\ProcedureStudyPolicy;
use App\Support\Audit;
use App\Support\CurrentHospital;
use App\Support\Forms;
use App\Support\Procedures;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Routing\Route as RouteDefinition;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
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
        // Signed versions of every procedure share one table, keyed by procedure name.
        Relation::morphMap(Procedures::MODELS);
        foreach (Procedures::MODELS as $model) {
            Gate::policy($model, ProcedureStudyPolicy::class);
        }

        // Procedure routes: {procedureStudy} is looked up in its own procedure's table (and
        // hospital, by the model's scope). Registered here so it also applies with cached routes.
        Route::pattern('procedure', implode('|', array_keys(Procedures::MODELS)));
        Route::pattern('procedureStudy', '[0-9]+');
        Route::bind('procedureStudy', fn (string $id, RouteDefinition $route) => Procedures::model($route->parameter('procedure'))::findOrFail($id));

        // Per-form permissions (App\Support\Forms): menus and shared pages need "any form" checks.
        Gate::define('record-studies', fn (User $user) => Forms::canRecordAny($user));
        Gate::define('sign-reports', fn (User $user) => Forms::canSignAny($user) || $user->can('prescriptions.sign'));

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
