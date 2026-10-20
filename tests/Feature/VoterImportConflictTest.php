<?php

use App\Models\CoordinatorAgent;
use App\Models\User;
use App\Models\VoterImportConflict;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

it('allows only active super admins to review voter import conflicts', function (string $role, bool $active, bool $allowed) {
    $user = User::factory()->create(['role' => $role, 'is_active' => $active]);
    expect(Gate::forUser($user)->allows('review-voter-conflicts'))->toBe($allowed);
})->with([
    ['super_admin', true, true], ['super_admin', false, false], ['admin', true, false], ['it', true, false],
]);

it('protects conflict details and photos from guests IT staff admins and coordinators', function () {
    Storage::fake('local');
    $conflict = VoterImportConflict::factory()->create(['photo_path' => 'voters/conflict/private.jpg']);
    Storage::disk('local')->put($conflict->photo_path, 'private conflict photo');
    $this->get(route('voters.conflicts.index'))->assertRedirect(route('login'));
    $this->get(route('voters.conflicts.photo', $conflict))->assertRedirect(route('login'));
    foreach (['admin', 'it'] as $role) {
        $this->actingAs(User::factory()->create(['role' => $role]), 'web')->get(route('voters.conflicts.index'))->assertForbidden();
        $this->get(route('voters.conflicts.photo', $conflict))->assertForbidden();
    }
    auth('web')->logout();
    $coordinator = CoordinatorAgent::factory()->create();
    expect(Gate::forUser($coordinator)->allows('review-voter-conflicts'))->toBeFalse();
    $this->actingAs($coordinator, 'coordinator')->get(route('voters.conflicts.index'))->assertRedirect(route('login'));
    $this->get(route('voters.conflicts.photo', $conflict))->assertRedirect(route('login'));
});

it('shows incoming details and both unit codes to super admins and serves the retained private photo', function () {
    Storage::fake('local');
    $conflict = VoterImportConflict::factory()->create([
        'record' => ['full_name' => '<script>unsafe</script> Incoming voter', 'age' => 35, 'polling_unit' => 'PDF address'],
        'photo_path' => 'voters/conflict/private.jpg',
    ]);
    Storage::disk('local')->put($conflict->photo_path, 'private conflict photo');

    $this->actingAs(User::factory()->superAdmin()->create(), 'web')->get(route('voters.conflicts.index'))
        ->assertOk()->assertSee('<script>unsafe</script> Incoming voter')->assertDontSee('<script>unsafe</script>', false)
        ->assertSee($conflict->vin)->assertSee($conflict->pollingUnit->delimitation_code)
        ->assertSee($conflict->existingPollingUnit->delimitation_code)->assertSee('PDF address')
        ->assertSee($conflict->existingVoter->full_name)->assertDontSee($conflict->photo_path)
        ->assertHeader('Cache-Control', 'max-age=0, no-store, private')->assertHeader('Pragma', 'no-cache');
    $this->get(route('voters.conflicts.photo', $conflict))->assertOk()->assertStreamedContent('private conflict photo');
    $this->get(route('voters.import.create'))->assertOk()->assertSee('Import conflicts');
});

it('does not serve files outside the private voters directory through conflict photos', function () {
    Storage::fake('local');
    $conflict = VoterImportConflict::factory()->create(['photo_path' => 'other/private.txt']);
    Storage::disk('local')->put('other/private.txt', 'private');

    $this->actingAs(User::factory()->superAdmin()->create(), 'web')->get(route('voters.conflicts.photo', $conflict))->assertNotFound();
});
