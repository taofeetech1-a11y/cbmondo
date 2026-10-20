<?php

use App\Models\Exco;
use App\Models\ExecutiveAssignment;
use App\Models\ExecutivePosition;
use App\Models\Lga;
use App\Models\User;
use Database\Factories\MembershipFactory;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;

test('permission matrix matches the staff roles', function (string $role, string $permission, bool $allowed) {
    $user = User::factory()->create(['role' => $role]);
    expect(Gate::forUser($user)->allows($permission))->toBe($allowed);
})->with(function () {
    $cases = [];
    foreach (User::PERMISSIONS as $permission) {
        $cases['super admin '.$permission] = ['super_admin', $permission, true];
        $cases['IT '.$permission] = ['it', $permission, $permission === 'import-voters'];
        $cases['admin '.$permission] = ['admin', $permission, in_array($permission, ['access-dashboard', 'view-voters', 'view-appointments', 'view-positions'])];
    }

    return $cases;
});

test('guests must log in for every dashboard endpoint', function (string $method, string $path) {
    $this->$method($path)->assertRedirect(route('login'));
})->with([
    ['get', '/membership'], ['get', '/membership/1'], ['get', '/membership/1/edit'], ['patch', '/membership/1'],
    ['get', '/membership/1/card'], ['get', '/membership/export/csv'], ['post', '/membership/cards/download'],
    ['get', '/membership/import'], ['post', '/membership/import/preview'], ['post', '/membership/import/confirm'],
    ['get', '/membership/import/template/csv'], ['get', '/membership/import/locations'],
    ['get', '/excos'], ['post', '/excos'], ['delete', '/excos/1'],
    ['get', '/executives'], ['get', '/executives/create'], ['post', '/executives'], ['delete', '/executives/1'],
    ['get', '/executive-positions'], ['post', '/executive-positions'], ['patch', '/executive-positions/1'], ['delete', '/executive-positions/1'],
    ['get', '/staff-users'], ['post', '/staff-users'], ['get', '/dashboard'],
]);

test('admins cannot use exports cards member editing or imports directly', function () {
    $member = MembershipFactory::new()->create();
    $this->actingAs(User::factory()->create());
    foreach (['/membership/export/csv', '/membership/export/xlsx', '/membership/export/json', '/membership/export/sql', '/membership/'.$member->id.'/card', '/membership/'.$member->id.'/card?download=1', '/membership/'.$member->id.'/edit', '/membership/import', '/membership/import/template/csv', '/membership/import/locations', '/staff-users'] as $path) {
        $this->get($path)->assertForbidden();
    }
    foreach (['/membership/cards/download', '/membership/import/preview', '/membership/import/confirm', '/staff-users'] as $path) {
        $this->post($path)->assertForbidden();
    }
    $this->patch(route('membership.update', $member), ['name' => 'Tampered'])->assertForbidden();
    $this->assertDatabaseHas('memberships', ['id' => $member->id, 'name' => $member->name]);
});

test('admins see dashboard data without appointment or other write controls', function () {
    $member = MembershipFactory::new()->create();
    $this->actingAs(User::factory()->create());
    $this->get('/membership')->assertOk()->assertDontSee('Export members')->assertDontSee('Download cards')->assertDontSee('Import members')->assertDontSee('id="export-trend"', false)->assertDontSee('id="export-chart"', false);
    $this->get(route('membership.show', $member))->assertOk()->assertDontSee('Download ID card')->assertDontSee('Update member')->assertDontSee(route('membership.card', $member));
    $this->get('/executives/create')->assertForbidden();
    $this->get('/executives')->assertOk()->assertDontSee('Assign executive');
    $this->get('/excos')->assertOk()->assertDontSee('+ Add Exco');
    $this->get('/executive-positions')->assertOk()->assertDontSee('New position name')->assertDontSee('Save name')->assertDontSee('Delete position');
});

test('super admins retain the ability to create positions assign and remove appointments', function () {
    $lga = Lga::factory()->create();
    $member = MembershipFactory::new()->create(['lga' => $lga->id]);
    $this->actingAs(User::factory()->superAdmin()->create());
    $this->post(route('executive-positions.store'), ['name' => 'Chairperson', 'level' => 'lga'])->assertSessionHasNoErrors();
    $position = ExecutivePosition::sole();
    $this->post(route('executives.store'), ['membership_id' => $member->id, 'executive_position_id' => $position->id])->assertSessionHasNoErrors();
    $this->delete(route('executives.destroy', $member->executiveAssignments()->sole()))->assertRedirect();
    $this->assertDatabaseCount('executive_assignments', 0);
    $this->assertModelExists($member);
});

test('admins cannot create update or delete appointments positions or staff through direct requests', function () {
    $assignment = ExecutiveAssignment::factory()->create();
    $exco = Exco::factory()->create();
    $staff = User::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->get(route('executives.index'))->assertOk()->assertSee($assignment->membership->name)
        ->assertDontSee('Remove appointment')->assertDontSee(route('executives.destroy', $assignment));
    $this->get(route('excos.index'))->assertOk()->assertSee($exco->membership->name)
        ->assertDontSee(route('excos.destroy', $exco->membership_id));
    $this->get(route('executive-positions.index'))->assertOk()->assertSee($assignment->position->name)
        ->assertDontSee('Save name')->assertDontSee('Delete position')->assertDontSee('New position name');
    foreach (['excos.create', 'executives.create'] as $route) {
        $this->get(route($route))->assertForbidden();
    }
    foreach (['excos.store', 'executives.store', 'executive-positions.store'] as $route) {
        $this->post(route($route), [])->assertForbidden();
    }
    $this->patch(route('executive-positions.update', $assignment->position), ['name' => 'Tampered'])->assertForbidden();
    $this->delete(route('executive-positions.destroy', $assignment->position))->assertForbidden();
    $this->delete(route('executives.destroy', $assignment))->assertForbidden();
    $this->delete(route('excos.destroy', $exco->membership_id))->assertForbidden();
    $this->post(route('coordinator-agents.store', $exco->membership_id), [])->assertForbidden();
    $this->patch(route('coordinator-agents.update', $exco->membership_id), [])->assertForbidden();
    $this->patch(route('staff-users.update', $staff), ['role' => 'super_admin'])->assertForbidden();

    $this->assertDatabaseCount('executive_assignments', 1);
    $this->assertDatabaseCount('executive_positions', 1);
    $this->assertDatabaseCount('excos', 1);
    expect($assignment->position->fresh()->name)->not->toBe('Tampered');
    expect($staff->fresh()->role)->toBe('admin');
});

test('admins can view their account but cannot change account details or delete their account', function () {
    $admin = User::factory()->create();
    $confirmation = URL::temporarySignedRoute('profile.email.confirm', now()->addMinutes(30), [
        'user' => $admin->id, 'email' => 'replacement@example.test', 'version' => $admin->session_version,
    ]);

    $this->actingAs($admin)->get(route('profile.edit'))->assertOk()
        ->assertSee($admin->name)->assertSee($admin->email)->assertSee('view-only access')
        ->assertDontSee('id="profile-current-password"', false)->assertDontSee('Delete Account');
    $this->patch(route('profile.update'), ['name' => 'Tampered', 'email' => 'replacement@example.test'])->assertForbidden();
    $this->get($confirmation)->assertForbidden();
    $this->delete(route('profile.destroy'), ['password' => 'password'])->assertForbidden();

    $this->assertDatabaseHas('users', ['id' => $admin->id, 'name' => $admin->name, 'email' => $admin->email, 'role' => 'admin']);
});

test('inactive and unassigned accounts cannot sign in or access dashboard', function (?string $role, bool $active) {
    $user = User::factory()->create(['role' => $role, 'is_active' => $active]);
    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
    $this->assertGuest();
    $this->actingAs($user)->get('/membership')->assertForbidden();
})->with([[null, true], ['super_admin', false], ['unknown', true]]);

test('staff responses are not cached and public member registration remains public', function () {
    $this->get('/')->assertOk();
    $this->post('/nc/registration', [])->assertSessionHasErrors();
    $this->actingAs(User::factory()->create())->get('/membership')->assertOk()->assertHeader('Cache-Control', 'max-age=0, no-store, private');
});
