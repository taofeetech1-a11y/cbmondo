<?php

namespace App\Providers;

use App\Models\CoordinatorAgent;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;
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
        foreach (User::PERMISSIONS as $permission) {
            Gate::define($permission, fn (User|CoordinatorAgent $user): bool => $user instanceof User && $user->hasPermission($permission));
        }

        Gate::define('view-voters', fn (User|CoordinatorAgent $user): bool => $user instanceof User
            ? $user->is_active && $user->role === 'super_admin'
            : (int) $user->membership?->pu > 0);

        Gate::define('update-voter', function (User|CoordinatorAgent $user, Voter $voter): Response {
            $allowed = $user instanceof User
                ? $user->is_active && $user->role === 'super_admin'
                : (int) $user->membership?->pu > 0 && (string) $user->membership->pu === (string) $voter->polling_unit_id;

            return $allowed ? Response::allow() : Response::denyAsNotFound();
        });
    }
}
