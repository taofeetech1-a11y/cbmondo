<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StaffUserController extends Controller
{
    public function index(): View
    {
        return view('dashboard.staff-users', ['users' => User::orderBy('name')->paginate(25)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'password' => ['required', 'confirmed', Password::min(12)],
        ]);
        $user = new User(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password']]);
        $user->role = $data['role'];
        $user->is_active = true;
        $user->save();

        return redirect()->route('staff-users.index')->with('status', 'Staff account created.');
    }

    public function update(Request $request, User $staffUser): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'is_active' => ['required', 'boolean'],
        ]);
        DB::transaction(function () use ($staffUser, $data): void {
            $superAdmins = User::where('role', 'super_admin')->where('is_active', true)->orderBy('id')->lockForUpdate()->get();
            $user = User::whereKey($staffUser->id)->lockForUpdate()->firstOrFail();
            if ($user->role === 'super_admin' && $user->is_active && $superAdmins->count() <= 1 && ($data['role'] !== 'super_admin' || ! $data['is_active'])) {
                throw ValidationException::withMessages(['role' => 'Keep at least one active super admin.']);
            }
            $user->role = $data['role'];
            $user->is_active = (bool) $data['is_active'];
            if (! $user->is_active) {
                $user->remember_token = null;
            }
            $user->save();
        }, 3);

        return redirect()->route('staff-users.index')->with('status', 'Staff access updated.');
    }
}
