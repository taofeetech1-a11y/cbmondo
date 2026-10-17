<?php

namespace App\Http\Middleware;

use App\Models\CoordinatorAgent;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('logout', 'coordinator.logout')) {
            return $next($request);
        }
        $user = $request->user();
        abort_unless(($user instanceof User || $user instanceof CoordinatorAgent) && $user->is_active, 403);
        if ($user instanceof User) {
            abort_unless(array_key_exists((string) $user->role, User::ROLES), 403);
        }
        $guard = $user instanceof CoordinatorAgent ? 'coordinator' : 'web';
        if (! $request->session()->has('account_version_'.$guard) && Auth::guard($guard)->viaRemember()) {
            $request->session()->put('account_version_'.$guard, $user->session_version);
        }
        if ((int) $request->session()->get('account_version_'.$guard, 0) !== (int) $user->session_version) {
            Auth::guard($guard)->logout();
            $request->session()->forget(['account_version_'.$guard, 'password_hash_'.$guard]);
            $request->session()->regenerate(true);
            $request->session()->regenerateToken();

            return redirect()->route($guard === 'coordinator' ? 'coordinator.login' : 'login');
        }
        if ($user instanceof CoordinatorAgent && ! $user->password_changed_at) {
            if (! $user->temporary_password_expires_at || $user->temporary_password_expires_at->isPast()) {
                Auth::guard('coordinator')->logout();

                return redirect()->route('coordinator.login')->withErrors(['cbm_id' => 'Ask your administrator for a new temporary password.']);
            }
            if (! $request->routeIs('coordinator.profile', 'coordinator.password', 'coordinator.logout')) {
                return redirect()->route('coordinator.profile')->with('status', 'Set your own password before accessing your polling unit.');
            }
        }

        return $next($request);
    }
}
