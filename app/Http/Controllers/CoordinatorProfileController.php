<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CoordinatorProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $agent = $request->user('coordinator');
        $agent->load('membership.lgaInfo', 'membership.wardInfo', 'membership.puInfo');

        return view('coordinator.profile', ['agent' => $agent, 'member' => $agent->membership]);
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password:coordinator'],
            'password' => ['required', 'string', Password::min(12), 'confirmed', 'different:current_password'],
        ]);
        $agent = $request->user('coordinator');
        $agent->password = $data['password'];
        $agent->password_changed_at = now();
        $agent->remember_token = Str::random(60);
        $agent->save();
        $request->session()->regenerate(true);

        return redirect()->route('coordinator.profile')->with('status', 'Password updated. Use your new password next time you sign in.');
    }

    public function updatePhoto(Request $request): RedirectResponse
    {
        $request->validateWithBag('photo', ['photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4096,max_height=4096']]);
        $agent = $request->user('coordinator');
        $previous = $agent->photo_path;
        $path = $request->file('photo')->store('coordinator-photos', 'local');
        abort_unless($path, 500, 'Photo could not be saved. Please try again.');
        try {
            $agent->photo_path = $path;
            $agent->save();
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }
        if ($previous) {
            Storage::disk('local')->delete($previous);
        }

        return redirect()->route('coordinator.profile')->with('status', 'Profile photo updated.');
    }

    public function photo(Request $request): BinaryFileResponse
    {
        $path = $request->user('coordinator')->photo_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), ['X-Content-Type-Options' => 'nosniff']);
    }
}
