<?php

use App\Models\CoordinatorAgent;
use App\Models\PollingUnit;
use App\Models\Voter;
use App\Services\RegisterImporter;
use Database\Factories\MembershipFactory;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

function canvassingAgent(PollingUnit $unit): CoordinatorAgent
{
    return CoordinatorAgent::factory()->for(MembershipFactory::new()->state(['lga' => $unit->ward->lga_id, 'ward' => $unit->ward_id, 'pu' => $unit->id]), 'membership')->create();
}

it('counts unit phone collection and distinguishes missing consent from refused consent', function () {
    $unit = PollingUnit::factory()->create();
    Voter::factory()->for($unit, 'pollingUnit')->create(['phone' => '08012345678', 'consent_given' => true, 'spoken_to' => true, 'apc_member' => true]);
    Voter::factory()->for($unit, 'pollingUnit')->create(['phone' => '08012345679', 'consent_given' => false]);
    Voter::factory()->for($unit, 'pollingUnit')->create(['phone' => null]);
    Voter::factory()->for(PollingUnit::factory(), 'pollingUnit')->create(['consent_given' => true, 'spoken_to' => true]);
    $this->actingAs(canvassingAgent($unit), 'coordinator')->get(route('coordinator.dashboard'))
        ->assertOk()->assertViewHas('stats', ['total' => 3, 'collected' => 2, 'pending' => 1, 'consented' => 1, 'not_consented' => 1, 'consent_unknown' => 1, 'spoken_to' => 1, 'apc_members' => 1])
        ->assertDontSee('points earned')->assertDontSee('Unit leaderboard');
});

it('saves capture responses once and preserves coordinator attribution', function (bool $consent) {
    $unit = PollingUnit::factory()->create();
    $agent = canvassingAgent($unit);
    $other = canvassingAgent($unit);
    $voter = Voter::factory()->for($unit, 'pollingUnit')->create();
    $this->actingAs($agent, 'coordinator')->patch(route('voters.update', $voter), ['phone' => '08012345678', 'spoken_to' => '1', 'apc_member' => '0', 'consent_given' => (int) $consent, 'phone_added_by' => $other->id, 'polling_unit_id' => 999])
        ->assertSessionHasNoErrors()->assertRedirect(route('coordinator.register'));
    expect($voter->fresh()->spoken_to)->toBeTrue();
    expect($voter->fresh()->apc_member)->toBeFalse();
    expect($voter->fresh()->consent_given)->toBe($consent);
    expect($voter->fresh()->phone_added_by)->toBe($agent->id);
    expect($voter->fresh()->polling_unit_id)->toBe($unit->id);
    $this->get(route('voters.edit', $voter))->assertOk()->assertSee('This record is read-only')->assertDontSee('name="consent_given"', false);
    $this->patch(route('voters.update', $voter), ['phone' => '08099999999', 'consent_given' => ! $consent])->assertNotFound();
    expect($voter->fresh()->consent_given)->toBe($consent);
})->with([true, false]);

it('rejects invalid capture responses without saving any fields', function (string $field) {
    $unit = PollingUnit::factory()->create();
    $voter = Voter::factory()->for($unit, 'pollingUnit')->create();
    $this->actingAs(canvassingAgent($unit), 'coordinator')->patch(route('voters.update', $voter), ['phone' => '08012345678', $field => 'invalid'])->assertSessionHasErrors($field);
    expect($voter->fresh()->phone)->toBeNull();
    expect($voter->fresh()->phone_added_by)->toBeNull();
})->with(['spoken_to', 'apc_member', 'consent_given']);

it('searches names and VINs and filters consent and collection without crossing polling units', function () {
    $unit = PollingUnit::factory()->create();
    $pending = Voter::factory()->for($unit, 'pollingUnit')->create(['full_name' => 'Ada Bello']);
    $collected = Voter::factory()->for($unit, 'pollingUnit')->create(['phone' => '08012345678', 'consent_given' => true]);
    Voter::factory()->for(PollingUnit::factory(), 'pollingUnit')->create(['full_name' => 'Ada Bello']);
    $this->actingAs(canvassingAgent($unit), 'coordinator');
    foreach (['Ada Bello', $pending->vin] as $search) {
        $this->get(route('coordinator.register', ['q' => $search]))->assertOk()->assertViewHas('voters', fn ($page) => $page->total() === 1 && $page->first()->id === $pending->id);
    }
    $this->get(route('coordinator.register', ['collection' => 'collected', 'consent' => 'given']))->assertOk()->assertViewHas('voters', fn ($page) => $page->total() === 1 && $page->first()->id === $collected->id);
    $this->get(route('coordinator.register', ['consent' => 'unknown']))->assertOk()->assertViewHas('voters', fn ($page) => $page->total() === 1 && $page->first()->id === $pending->id);
    $this->get(route('coordinator.register', ['collection' => 'invalid']))->assertSessionHasErrors('collection');
    $this->get(route('coordinator.register', ['q' => str_repeat('a', 101)]))->assertSessionHasErrors('q');
});

it('allows private photos of completed unit voters but denies other unit details', function () {
    Storage::fake('local');
    $unit = PollingUnit::factory()->create();
    $own = Voter::factory()->for($unit, 'pollingUnit')->create(['phone' => '08012345678', 'photo_path' => 'voters/test.jpg']);
    $outside = Voter::factory()->for(PollingUnit::factory(), 'pollingUnit')->create(['phone' => '08012345678']);
    Storage::disk('local')->put($own->photo_path, 'private photo');
    $this->actingAs(canvassingAgent($unit), 'coordinator')->get(route('voters.photo', $own))->assertOk()->assertStreamedContent('private photo');
    $this->get(route('voters.edit', $outside))->assertNotFound();
    $this->get(route('voters.photo', $outside))->assertNotFound();
});

it('retains capture responses when the PDF is imported again', function () {
    Storage::fake('local');
    $unit = PollingUnit::factory()->create();
    $voter = Voter::factory()->for($unit, 'pollingUnit')->create(['phone' => '08012345678', 'spoken_to' => true, 'apc_member' => false, 'consent_given' => true]);
    $source = Storage::disk('local')->path('source.pdf');
    Storage::disk('local')->put('source.pdf', 'pdf');
    Process::fake(function (PendingProcess $process) use ($voter) {
        File::put($process->command[3].'/records.json', json_encode(['expected' => 1, 'records' => [['vin' => $voter->vin, 'full_name' => 'Updated PDF name']]], JSON_THROW_ON_ERROR));

        return Process::result();
    });
    app(RegisterImporter::class)->import($source, $unit);
    expect($voter->fresh()->full_name)->toBe('Updated PDF name');
    expect($voter->fresh()->phone)->toBe('08012345678');
    expect($voter->fresh()->spoken_to)->toBeTrue();
    expect($voter->fresh()->apc_member)->toBeFalse();
    expect($voter->fresh()->consent_given)->toBeTrue();
});
