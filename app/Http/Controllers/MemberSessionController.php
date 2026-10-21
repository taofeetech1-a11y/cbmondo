<?php

namespace App\Http\Controllers;

use App\Support\MembershipContact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MemberSessionController extends Controller
{
    public function create(): View
    {
        return view('member.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['phone' => ['required', 'string', 'max:40'], 'password' => ['required', 'string', 'max:255']]);
        $phone = MembershipContact::phone($data['phone']);
        $key = 'member-login:'.hash('sha256', $phone.'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['phone' => 'Too many attempts. Please try again in '.RateLimiter::availableIn($key).' seconds.']);
        }
        if (! Auth::guard('member')->attempt(['phone' => $phone, 'password' => $data['password'], 'is_active' => true])) {
            RateLimiter::hit($key);
            throw ValidationException::withMessages(['phone' => 'The phone number or password is incorrect.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->session()->forget(['member_activation', 'password_hash_member']);

        return redirect()->route('member.profile');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('member')->logout();
        $request->session()->forget(['member_activation', 'password_hash_member']);
        $request->session()->regenerate(true);
        $request->session()->regenerateToken();

        return redirect()->route('member.login');
    }
}
