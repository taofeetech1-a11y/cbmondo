<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('super admins can create staff accounts and change their access', function () {
    $super = User::factory()->superAdmin()->create();
    $this->actingAs($super)->post(route('staff-users.store'), ['name' => 'Staff Person', 'email' => 'staff@example.com', 'role' => 'admin', 'password' => 'long-secure-password', 'password_confirmation' => 'long-secure-password'])->assertSessionHasNoErrors()->assertRedirect(route('staff-users.index'));
    $staff = User::where('email', 'staff@example.com')->sole();
    expect($staff->role)->toBe('admin');
    expect(Hash::check('long-secure-password', $staff->password))->toBeTrue();
    $this->patch(route('staff-users.update', $staff), ['role' => 'super_admin', 'is_active' => 1])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('users', ['id' => $staff->id, 'role' => 'super_admin']);
    $this->patch(route('staff-users.update', $staff), ['role' => 'admin', 'is_active' => 0])->assertSessionHasNoErrors();
    $this->actingAs($staff->fresh())->get('/membership')->assertForbidden();
});

test('admins cannot promote themselves through staff or profile endpoints', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->patch(route('staff-users.update', $user), ['role' => 'super_admin', 'is_active' => 1])->assertForbidden();
    $this->patch(route('profile.update'), ['name' => $user->name, 'email' => $user->email, 'role' => 'super_admin', 'is_active' => true])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'admin']);
});

test('last active super admin cannot be disabled demoted or deleted', function () {
    $user = User::factory()->superAdmin()->create();
    $this->actingAs($user)->patch(route('staff-users.update', $user), ['role' => 'admin', 'is_active' => 1])->assertSessionHasErrors('role');
    $this->patch(route('staff-users.update', $user), ['role' => 'super_admin', 'is_active' => 0])->assertSessionHasErrors('role');
    $this->delete(route('profile.destroy'), ['password' => 'password'])->assertSessionHasErrorsIn('userDeletion', 'password');
    $this->assertModelExists($user);
});

test('super admin bootstrap creates a hashed account without default credentials', function () {
    $this->artisan('staff:create-super-admin')->expectsQuestion('Full name', 'Super Admin')->expectsQuestion('Email address', 'super@example.com')->expectsQuestion('Password (at least 12 characters)', 'long-secure-password')->expectsQuestion('Confirm password', 'long-secure-password')->assertSuccessful();
    $user = User::where('email', 'super@example.com')->sole();
    expect($user->role)->toBe('super_admin');
    expect($user->is_active)->toBeTrue();
    expect(Hash::check('long-secure-password', $user->password))->toBeTrue();
});
