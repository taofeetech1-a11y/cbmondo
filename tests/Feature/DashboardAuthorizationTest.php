<?php

use App\Models\ExecutivePosition;
use App\Models\Lga;
use App\Models\User;
use Database\Factories\MembershipFactory;
use Illuminate\Support\Facades\Gate;

test('permission matrix matches the two staff roles', function (string $role, string $permission, bool $allowed) {
    $user = User::factory()->create(['role' => $role]);
    expect(Gate::forUser($user)->allows($permission))->toBe($allowed);
})->with(function () {
    $cases = [];
    foreach (User::PERMISSIONS as $permission) {
        $cases['super admin '.$permission] = ['super_admin', $permission, true];
        $cases['admin '.$permission] = ['admin', $permission, in_array($permission, ['access-dashboard', 'manage-appointments', 'manage-positions'])];
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

test('admins see data and appointment controls but not restricted actions', function () {
    $member = MembershipFactory::new()->create();
    $this->actingAs(User::factory()->create());
    $this->get('/membership')->assertOk()->assertDontSee('Export members')->assertDontSee('Download cards')->assertDontSee('Import members')->assertDontSee('id="export-trend"', false)->assertDontSee('id="export-chart"', false);
    $this->get(route('membership.show', $member))->assertOk()->assertDontSee('Download ID card')->assertDontSee('Update member')->assertDontSee(route('membership.card', $member));
    $this->get('/executives/create')->assertOk();
    $this->get('/executive-positions')->assertOk();
});

test('admins can create positions assign and remove appointments', function () {
    $lga = Lga::factory()->create();
    $member = MembershipFactory::new()->create(['lga' => $lga->id]);
    $this->actingAs(User::factory()->create());
    $this->post(route('executive-positions.store'), ['name' => 'Chairperson', 'level' => 'lga'])->assertSessionHasNoErrors();
    $position = ExecutivePosition::sole();
    $this->post(route('executives.store'), ['membership_id' => $member->id, 'executive_position_id' => $position->id])->assertSessionHasNoErrors();
    $this->delete(route('executives.destroy', $member->executiveAssignments()->sole()))->assertRedirect();
    $this->assertDatabaseCount('executive_assignments', 0);
    $this->assertModelExists($member);
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
