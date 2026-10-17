<?php

namespace App\Http\Controllers;

use App\Models\CoordinatorAgent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CoordinatorSessionController extends Controller
{
    public function create(): View
    {
        return view('coordinator.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['cbm_id' => ['required', 'string', 'max:255'], 'password' => ['required', 'string']]);
        $cbmId = Str::lower(trim($data['cbm_id']));
        $key = 'coordinator-login:'.hash('sha256', $cbmId.'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['cbm_id' => 'Too many attempts. Try again in '.RateLimiter::availableIn($key).' seconds.']);
        }
        $agent = CoordinatorAgent::whereHas('membership', fn ($query) => $query->whereRaw('LOWER(cbm_id) = ?', [$cbmId]))->first();
        $eligible = $agent && $agent->is_active && ($agent->password_changed_at || $agent->temporary_password_expires_at?->isFuture());
        if (! Auth::guard('coordinator')->attempt(['id' => $eligible ? $agent->id : 0, 'is_active' => true, 'password' => $data['password']])) {
            RateLimiter::hit($key);
            throw ValidationException::withMessages(['cbm_id' => 'The CBM ID or password is incorrect.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->session()->forget('password_hash_coordinator');
        $request->session()->put('account_version_coordinator', $agent->session_version);

        return redirect()->route($agent->password_changed_at ? 'coordinator.dashboard' : 'coordinator.profile');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('coordinator')->logout();
        $request->session()->forget('password_hash_coordinator');
        $request->session()->regenerate(true);
        $request->session()->regenerateToken();

        return redirect()->route('coordinator.login');
    }
}
