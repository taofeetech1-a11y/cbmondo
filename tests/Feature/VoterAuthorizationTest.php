<?php

use App\Models\CoordinatorAgent;
use App\Models\PollingUnit;
use App\Models\User;
use App\Models\Voter;
use Database\Factories\MembershipFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

function voterCoordinatorForUnit(PollingUnit $unit): CoordinatorAgent
{
    return CoordinatorAgent::factory()->for(MembershipFactory::new()->state([
        'lga' => $unit->ward->lga_id, 'ward' => $unit->ward_id, 'pu' => $unit->id,
    ]), 'membership')->create();
}

it('allows only active super admins to browse all voters among staff roles', function (string $role, bool $active, bool $allowed) {
    $staff = User::factory()->create(['role' => $role, 'is_active' => $active]);
    expect(Gate::forUser($staff)->allows('view-voters'))->toBe($allowed);
    expect(Gate::forUser($staff)->allows('import-voters'))->toBe($active && in_array($role, ['super_admin', 'it'], true));
    $response = $this->actingAs($staff, 'web')->get(route('voters.index'));
    $response->assertStatus($allowed ? 200 : 403);
})->with([
    'super admin' => ['super_admin', true, true],
    'admin' => ['admin', true, false],
    'IT' => ['it', true, false],
    'inactive super admin' => ['super_admin', false, false],
    'inactive IT' => ['it', false, false],
]);

it('shows all units and unlinked voters to a super admin', function () {
    $units = PollingUnit::factory()->count(2)->create();
    foreach ($units as $unit) {
        Voter::factory()->for($unit, 'pollingUnit')->create();
    }
    Voter::factory()->create(['full_name' => 'Unlinked voter']);
    $this->actingAs(User::factory()->superAdmin()->create(), 'web')
        ->get(route('voters.index'))->assertOk()->assertSee('Unlinked voter')
        ->assertViewHas('voters', fn ($voters): bool => $voters->total() === 3)
        ->assertViewHas('totalUnits', 2);
});

it('limits coordinator voters counts status cards and location choices to their polling unit', function () {
    $unit = PollingUnit::factory()->create();
    $other = PollingUnit::factory()->create();
    Voter::factory()->for($unit, 'pollingUnit')->create(['full_name' => 'Own voter']);
    Voter::factory()->for($other, 'pollingUnit')->create(['full_name' => 'Outside voter']);
    Voter::factory()->create(['full_name' => 'Unlinked voter']);
    $agent = voterCoordinatorForUnit($unit);

    $this->actingAs($agent, 'coordinator')->get(route('voters.index'))->assertOk()
        ->assertSee('Own voter')->assertDontSee('Outside voter')->assertDontSee('Unlinked voter')
        ->assertDontSee('Import a register')->assertDontSee('Import register')
        ->assertViewHas('totalUnits', 1)->assertViewHas('uploadedUnits', 1)
        ->assertViewHas('unitStatuses', fn ($units): bool => $units->total() === 1 && $units->first()->id === $unit->id)
        ->assertViewHas('lgas', fn ($lgas): bool => $lgas->count() === 1 && $lgas->first()->id === $unit->ward->lga_id);

    foreach ([['polling_unit_id' => $other->id], ['lga' => $other->ward->lga_id], ['q' => 'Outside']] as $filters) {
        $this->get(route('voters.index', $filters))->assertOk()->assertDontSee('Outside voter')
            ->assertViewHas('voters', fn ($voters): bool => $voters->total() === 0);
    }
    $this->get(route('coordinator.register'))->assertRedirect(route('voters.index'));
});

it('allows coordinators to update only phone numbers in their own polling unit', function () {
    $unit = PollingUnit::factory()->create();
    $own = Voter::factory()->for($unit, 'pollingUnit')->create();
    $other = Voter::factory()->for(PollingUnit::factory(), 'pollingUnit')->create(['phone' => '08011111111']);
    $unlinked = Voter::factory()->create();
    $this->actingAs(voterCoordinatorForUnit($unit), 'coordinator')
        ->get(route('voters.edit', $own))->assertOk();
    $this->patch(route('voters.update', $own), ['phone' => '08012345678'])->assertSessionHasNoErrors();
    expect($own->fresh()->phone)->toBe('08012345678');
    foreach ([$other, $unlinked] as $voter) {
        $this->get(route('voters.edit', $voter))->assertNotFound();
        $this->patch(route('voters.update', $voter), ['phone' => '08099999999'])->assertNotFound();
    }
    expect($other->fresh()->phone)->toBe('08011111111');
    expect($unlinked->fresh()->phone)->toBeNull();
});

it('denies voter access to coordinators without a polling unit', function () {
    $this->actingAs(CoordinatorAgent::factory()->create(), 'coordinator')
        ->get(route('voters.index'))->assertForbidden();
});

it('blocks coordinators from uploading PDF registers', function () {
    Process::fake();
    $this->actingAs(voterCoordinatorForUnit(PollingUnit::factory()->create()), 'coordinator')
        ->get(route('voters.import.create'))->assertRedirect(route('login'));
    $this->post(route('voters.import'))->assertRedirect(route('login'));
    Process::assertNothingRan();
});

it('lets IT sign in and upload while refusing directory edit and staff administration access', function () {
    $it = User::factory()->create(['role' => 'it']);
    $unit = PollingUnit::factory()->create();
    $voter = Voter::factory()->create();
    $this->withSession(['url.intended' => route('voters.index')])
        ->post(route('login'), ['email' => $it->email, 'password' => 'password'])
        ->assertRedirect(route('voters.import.create'));
    $this->get(route('voters.import.create'))->assertOk()->assertSee('Staff workspace')->assertDontSee('Voter directory');
    $this->get(route('voters.index'))->assertForbidden();
    $this->get(route('voters.edit', $voter))->assertForbidden();
    $this->patch(route('voters.update', $voter), ['phone' => '08012345678'])->assertForbidden();
    $this->get(route('staff-users.index'))->assertForbidden();
    $this->get(route('membership.index'))->assertForbidden();

    Storage::fake('local');
    Storage::fake('public');
    Process::fake(function (PendingProcess $process) {
        File::put($process->command[3].'/private.jpg', 'private voter photo');
        File::put($process->command[3].'/records.json', json_encode([
            'header' => null, 'expected' => 1,
            'records' => [[
                'serial' => 1, 'vin' => 'AAAA BBBB CCCC DDDD 123', 'full_name' => 'Imported Voter',
                'surname' => 'Voter', 'other_names' => 'Imported', 'occupation' => null,
                'gender' => 'Female', 'age' => 30, 'photo' => 'private.jpg',
            ]],
        ]));

        return Process::result();
    });
    $this->post(route('voters.import'), [
        'lga' => $unit->ward->lga_id, 'ward' => $unit->ward_id, 'polling_unit_id' => $unit->id,
        'pdf' => UploadedFile::fake()->create('register.pdf', 1, 'application/pdf'),
    ])->assertSessionHasNoErrors()->assertRedirect(route('voters.import.create'));
    expect($unit->fresh()->register_uploaded_at)->not->toBeNull();
    expect($voter->fresh()->phone)->toBeNull();
    Process::assertRan(fn (PendingProcess $process): bool => $process->command[1] === base_path('scripts/extract-register.mjs'));
    expect(Storage::disk('local')->allFiles())->toBe(['voters/private.jpg']);
    expect(Storage::disk('local')->get('voters/private.jpg'))->toBe('private voter photo');
    Storage::disk('public')->assertMissing('voters/private.jpg');
});

it('lets a super admin create IT staff through the staff account form', function () {
    $this->actingAs(User::factory()->superAdmin()->create(), 'web')
        ->get(route('staff-users.index'))->assertOk()->assertSee('value="it"', false);
    $this->post(route('staff-users.store'), [
        'name' => 'IT Operator', 'email' => 'it@example.test', 'role' => 'it',
        'password' => 'long-secure-password', 'password_confirmation' => 'long-secure-password',
    ])->assertSessionHasNoErrors()->assertRedirect(route('staff-users.index'));
    $this->assertDatabaseHas('users', ['email' => 'it@example.test', 'role' => 'it', 'is_active' => true]);
});

it('protects voter photos with the same polling unit restrictions', function () {
    $unit = PollingUnit::factory()->create();
    $own = Voter::factory()->for($unit, 'pollingUnit')->create(['photo_path' => 'voters/own.jpg']);
    $outside = Voter::factory()->for(PollingUnit::factory(), 'pollingUnit')->create(['photo_path' => 'voters/outside.jpg']);
    Storage::fake('local');
    Storage::disk('local')->put('voters/own.jpg', 'own photo');
    Storage::disk('local')->put('voters/outside.jpg', 'outside photo');

    $this->get(route('voters.photo', $own))->assertRedirect(route('login'));
    $this->actingAs(voterCoordinatorForUnit($unit), 'coordinator')
        ->get(route('voters.photo', $own))->assertOk()->assertStreamedContent('own photo');
    $this->get(route('voters.photo', $outside))->assertNotFound();
    $this->actingAs(User::factory()->create(['role' => 'it']), 'web')
        ->get(route('voters.photo', $own))->assertForbidden();
    $this->actingAs(User::factory()->superAdmin()->create(), 'web')
        ->get(route('voters.photo', $outside))->assertOk()->assertStreamedContent('outside photo');
});

it('moves existing voter photos out of public storage while retaining their content', function () {
    Storage::fake('public');
    Storage::fake('local');
    Storage::disk('public')->put('voters/legacy.jpg', 'legacy photo');
    Storage::disk('public')->put('other/unrelated.jpg', 'unrelated photo');

    $this->artisan('voters:secure-photos')->assertSuccessful();
    expect(Storage::disk('local')->get('voters/legacy.jpg'))->toBe('legacy photo');
    Storage::disk('public')->assertMissing('voters/legacy.jpg');
    Storage::disk('public')->assertExists('other/unrelated.jpg');
    $this->artisan('voters:secure-photos')->assertSuccessful();
    expect(Storage::disk('local')->get('voters/legacy.jpg'))->toBe('legacy photo');
});
