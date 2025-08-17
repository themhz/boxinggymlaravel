<?php

namespace App\Providers;

//use Illuminate\Support\ServiceProvider;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;


class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
        // Admin can do anything
        Gate::before(fn ($user) => $user->role === 'admin' ? true : null);

        // Explicit gates you’ll reference in routes
        Gate::define('manage-membership-plans', fn ($user) => $user->role === 'admin');
        Gate::define('manage-users', fn ($user) => $user->role === 'admin');
        Gate::define('manage-offers', fn ($user) => $user->role === 'admin');
        Gate::define('manage-payment-methods', fn($user) => $user->role === 'admin');
        Gate::define('manage-exercises', fn($user) => $user->role === 'admin');
        Gate::define('manage-class-sessions', fn($user) => $user->role === 'admin');
        Gate::define('manage-session-exercises', fn($user) => $user->role === 'admin');
        Gate::define('manage-session-exercise-students', fn($user) => $user->role === 'admin');

        // keep any other gates you need
        Gate::define('students.create', fn ($user) => $user->role === 'admin');
    }
}
