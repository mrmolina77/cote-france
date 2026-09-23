<?php

namespace App\Providers;

use App\Models\Team;
use App\Policies\TeamPolicy;
use App\Support\FinancialPermissions;
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

        foreach (FinancialPermissions::names() as $permission) {
            Gate::define($permission, fn ($user) => FinancialPermissions::allows($user, $permission));
        }
    }
}
