<?php

namespace App\Providers;

use App\Models\Team;
use App\Policies\TeamPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Team::class => TeamPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('manage-inscripciones', function ($user) {
            return optional($user->role)->roles_codigo === 'admin';
        });

        Gate::define('manage-conceptos-cobro', function ($user) {
            return optional($user->role)->roles_codigo === 'admin';
        });

        Gate::define('manage-metodos-pago', function ($user) {
            return optional($user->role)->roles_codigo === 'admin';
        });

        Gate::define('manage-cargos', function ($user) {
            return optional($user->role)->roles_codigo === 'admin';
        });

        Gate::define('manage-pagos', function ($user) {
            return optional($user->role)->roles_codigo === 'admin';
        });

        Gate::define('cancel-pagos', function ($user) {
            return optional($user->role)->roles_codigo === 'admin';
        });
    }
}
