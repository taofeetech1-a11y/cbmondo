<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use App\Notifications\VerifyEmailChange;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->update($request->safe()->only('name'));
        if ($request->validated('email') !== $user->email) {
            $verificationUrl = URL::temporarySignedRoute('profile.email.confirm', now()->addMinutes(30), [
                'user' => $user->id, 'email' => $request->validated('email'), 'version' => $user->session_version,
            ]);
            Notification::route('mail', $request->validated('email'))->notify(new VerifyEmailChange($verificationUrl));

            return Redirect::route('profile.edit')->with('status', 'Confirm the link sent to your new email address. Your current sign-in email is unchanged until verification.');
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function confirmEmail(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->is($request->user()), 403);
        DB::transaction(function () use ($request, $user): void {
            $account = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $request->query('version') === $account->session_version, 403);
            $data = $request->validate(['email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($account->id)]]);
            $account->email = $data['email'];
            $account->email_verified_at = now();
            $account->session_version++;
            $account->remember_token = Str::random(60);
            $account->save();
            $request->user()->setRawAttributes($account->getAttributes(), true);
            $request->session()->put('account_version_web', $account->session_version);
        });
        $request->session()->regenerate(true);

        return redirect()->route('profile.edit')->with('status', 'Email address verified and updated. Other sessions have been revoked.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        DB::transaction(function () use ($user): void {
            $superAdmins = User::where('role', 'super_admin')->where('is_active', true)->orderBy('id')->lockForUpdate()->get();
            if ($superAdmins->contains('id', $user->id) && $superAdmins->count() <= 1) {
                throw ValidationException::withMessages(['password' => 'Keep at least one active super admin.'])->errorBag('userDeletion');
            }
            Auth::logout();
            $user->delete();
        }, 3);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
