<?php

use App\Jobs\ImportVoterRegister;
use App\Models\PollingUnit;
use App\Models\User;
use App\Models\Voter;
use App\Models\VoterRegisterImport;
use App\Models\Ward;
use App\Services\RegisterImporter;
use Illuminate\Http\UploadedFile;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Queue::fake();
});

function finishLatestVoterImport(): VoterRegisterImport
{
    $import = VoterRegisterImport::latest('id')->firstOrFail();
    (new ImportVoterRegister($import->id))->handle(app(RegisterImporter::class));

    return $import->fresh();
}

it('queues extraction outside the web request and gives the worker a bounded time budget', function () {
    $originalLimit = (int) ini_get('max_execution_time');
    config(['services.voter_import_timeout' => 600]);
    $unit = PollingUnit::factory()->create();
    Storage::fake('local');
    Process::fake(function (PendingProcess $process) {
        expect((int) ini_get('max_execution_time'))->toBe(660);
        File::put($process->command[3].'/records.json', json_encode([
            'header' => null, 'expected' => 0, 'records' => [],
        ], JSON_THROW_ON_ERROR));

        return Process::result();
    });

    try {
        set_time_limit(30);
        $this->actingAs(User::factory()->superAdmin()->create(), 'web')
            ->post(route('voters.import'), [
                'lga' => $unit->ward->lga_id, 'ward' => $unit->ward_id, 'polling_unit_id' => $unit->id,
                'pdf' => UploadedFile::fake()->create('register.pdf', 1, 'application/pdf'),
            ])->assertSessionHasNoErrors()->assertRedirect(route('voters.import.create'));

        Process::assertNothingRan();
        expect((int) ini_get('max_execution_time'))->toBe(30);
        finishLatestVoterImport();

        expect($unit->fresh()->register_uploaded_at)->not->toBeNull();
        Process::assertRan(fn (PendingProcess $process): bool => $process->command[1] === base_path('scripts/extract-register.mjs'));
        expect(Storage::disk('local')->allFiles())->toBeEmpty();
    } finally {
        set_time_limit($originalLimit);
    }
});

it('shows the location selectors to a signed in super admin', function () {
    $unit = PollingUnit::factory()->create();

    $this->actingAs(User::factory()->superAdmin()->create(), 'web')
        ->get('/voters/import')->assertOk()
        ->assertSee($unit->ward->lga->name)
        ->assertSee('name="lga"', false)
        ->assertSee('name="ward"', false)
        ->assertSee('name="polling_unit_id"', false);
});

it('requires a location before starting extraction', function () {
    Process::fake();
    Storage::fake('local');

    $this->actingAs(User::factory()->superAdmin()->create(), 'web')
        ->post(route('voters.import'), [
            'pdf' => UploadedFile::fake()->create('register.pdf', 1, 'application/pdf'),
        ])->assertSessionHasErrors(['lga', 'ward', 'polling_unit_id']);

    Process::assertNothingRan();
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
    $this->assertDatabaseCount('voters', 0);
});

it('rejects a location outside the selected hierarchy', function (string $invalidField) {
    $unit = PollingUnit::factory()->create();
    $otherUnit = PollingUnit::factory()->create();
    $data = [
        'lga' => $unit->ward->lga_id,
        'ward' => $unit->ward_id,
        'polling_unit_id' => $unit->id,
        'pdf' => UploadedFile::fake()->create('register.pdf', 1, 'application/pdf'),
    ];
    $data[$invalidField] = $invalidField === 'ward' ? $otherUnit->ward_id : $otherUnit->id;
    Process::fake();

    $this->actingAs(User::factory()->superAdmin()->create(), 'web')
        ->post(route('voters.import'), $data)->assertSessionHasErrors($invalidField);

    Process::assertNothingRan();
    $this->assertDatabaseCount('voters', 0);
})->with(['ward', 'polling_unit_id']);

it('rejects a polling unit that does not exist', function () {
    $ward = Ward::factory()->create();
    Process::fake();

    $this->actingAs(User::factory()->superAdmin()->create(), 'web')
        ->post(route('voters.import'), [
            'lga' => $ward->lga_id,
            'ward' => $ward->id,
            'polling_unit_id' => 999999,
            'pdf' => UploadedFile::fake()->create('register.pdf', 1, 'application/pdf'),
        ])->assertSessionHasErrors('polling_unit_id');

    Process::assertNothingRan();
    $this->assertDatabaseCount('voters', 0);
});

it('restores location choices after validation fails', function () {
    $unit = PollingUnit::factory()->create();

    $this->actingAs(User::factory()->superAdmin()->create(), 'web')
        ->from('/voters/import')->post(route('voters.import'), [
            'lga' => $unit->ward->lga_id,
            'ward' => $unit->ward_id,
            'polling_unit_id' => $unit->id,
        ])->assertRedirect('/voters/import')->assertSessionHasErrors('pdf');

    $this->get('/voters/import')->assertOk()
        ->assertSee($unit->ward->name)->assertSee($unit->name)
        ->assertSee('value="'.$unit->id.'" selected', false);
});

it('links imported and reimported voters to the selected polling unit', function () {
    $unit = PollingUnit::factory()->create();
    Storage::fake('local');
    $workDirectories = [];
    Process::fake(function (PendingProcess $process) use (&$workDirectories) {
        $work = $process->command[3];
        $workDirectories[] = $work;
        File::put($work.'/records.json', json_encode([
            'header' => ['state' => 'ONDO', 'lga' => 'PDF LGA', 'registration_area' => 'PDF WARD', 'polling_unit' => 'PDF UNIT'],
            'expected' => 1,
            'records' => [[
                'serial' => 1, 'vin' => 'AAAA BBBB CCCC DDDD 123',
                'full_name' => 'Example Voter', 'surname' => 'Voter', 'other_names' => 'Example',
                'occupation' => null, 'gender' => 'Male', 'age' => 30, 'photo' => null,
            ]],
        ], JSON_THROW_ON_ERROR));

        return Process::result();
    });

    $this->actingAs(User::factory()->superAdmin()->create(), 'web');
    for ($attempt = 0; $attempt < 2; $attempt++) {
        $this->post(route('voters.import'), [
            'lga' => $unit->ward->lga_id,
            'ward' => $unit->ward_id,
            'polling_unit_id' => $unit->id,
            'pdf' => UploadedFile::fake()->create('register.pdf', 1, 'application/pdf'),
        ])->assertSessionHasNoErrors()->assertRedirect(route('voters.import.create'));
        finishLatestVoterImport();
        if ($attempt === 0) {
            Voter::sole()->update(['phone' => '08012345678']);
        }
    }

    $this->assertDatabaseCount('voters', 1);
    $this->assertDatabaseHas('voters', [
        'vin' => 'AAAA BBBB CCCC DDDD 123', 'polling_unit_id' => $unit->id,
        'polling_unit' => 'PDF UNIT',
        'phone' => '08012345678',
    ]);
    expect(Voter::sole()->pollingUnit->is($unit))->toBeTrue();
    expect($unit->fresh()->register_uploaded_at)->not->toBeNull();
    Process::assertRanTimes(fn (PendingProcess $process): bool => $process->command[1] === base_path('scripts/extract-register.mjs'), 2);
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
    foreach ($workDirectories as $work) {
        expect(File::exists($work))->toBeFalse();
    }
});

it('does not mark a polling unit uploaded when extraction fails', function () {
    $unit = PollingUnit::factory()->create();
    Storage::fake('local');
    Process::fake(fn () => Process::result(errorOutput: 'Extraction failed', exitCode: 1));

    $this->actingAs(User::factory()->superAdmin()->create(), 'web')
        ->post(route('voters.import'), [
            'lga' => $unit->ward->lga_id, 'ward' => $unit->ward_id, 'polling_unit_id' => $unit->id,
            'pdf' => UploadedFile::fake()->create('register.pdf', 1, 'application/pdf'),
        ])->assertRedirect(route('voters.import.create'));
    expect(finishLatestVoterImport()->status)->toBe('failed');

    expect($unit->fresh()->register_uploaded_at)->toBeNull();
    $this->assertDatabaseCount('voters', 0);
    Process::assertRan(fn (PendingProcess $process): bool => $process->command[1] === base_path('scripts/extract-register.mjs'));
    expect(Storage::disk('local')->files('register-imports'))->toHaveCount(1);
});

it('marks a successfully imported empty register as uploaded', function () {
    $unit = PollingUnit::factory()->create();
    Storage::fake('local');
    Process::fake(function (PendingProcess $process) {
        File::put($process->command[3].'/records.json', json_encode([
            'header' => ['state' => 'ONDO', 'lga' => 'PDF LGA', 'registration_area' => 'PDF WARD', 'polling_unit' => 'PDF UNIT'],
            'expected' => 0, 'records' => [],
        ], JSON_THROW_ON_ERROR));

        return Process::result();
    });

    $this->actingAs(User::factory()->superAdmin()->create(), 'web')
        ->post(route('voters.import'), [
            'lga' => $unit->ward->lga_id, 'ward' => $unit->ward_id, 'polling_unit_id' => $unit->id,
            'pdf' => UploadedFile::fake()->create('register.pdf', 1, 'application/pdf'),
        ])->assertSessionHasNoErrors()->assertRedirect(route('voters.import.create'));
    finishLatestVoterImport();

    expect($unit->fresh()->register_uploaded_at)->not->toBeNull();
    $this->assertDatabaseCount('voters', 0);
    Process::assertRan(fn (PendingProcess $process): bool => $process->command[1] === base_path('scripts/extract-register.mjs'));
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
});

it('uses the selected location when the PDF has no recognizable header', function () {
    $unit = PollingUnit::factory()->create();
    Storage::fake('local');
    Process::fake(function (PendingProcess $process) {
        File::put($process->command[3].'/records.json', json_encode([
            'header' => null, 'expected' => 1,
            'records' => [[
                'serial' => 1, 'vin' => 'AAAA BBBB CCCC DDDD 123',
                'full_name' => 'Example Voter', 'surname' => 'Voter', 'other_names' => 'Example',
                'occupation' => null, 'gender' => 'Male', 'age' => 30, 'photo' => null,
            ]],
        ], JSON_THROW_ON_ERROR));

        return Process::result();
    });

    $this->actingAs(User::factory()->superAdmin()->create(), 'web')
        ->post(route('voters.import'), [
            'lga' => $unit->ward->lga_id, 'ward' => $unit->ward_id, 'polling_unit_id' => $unit->id,
            'pdf' => UploadedFile::fake()->create('register.pdf', 1, 'application/pdf'),
        ])->assertSessionHasNoErrors()->assertRedirect(route('voters.import.create'));
    finishLatestVoterImport();

    $this->assertDatabaseHas('voters', [
        'vin' => 'AAAA BBBB CCCC DDDD 123', 'polling_unit_id' => $unit->id,
        'lga' => $unit->ward->lga->name, 'registration_area' => $unit->ward->name,
        'polling_unit' => $unit->name,
    ]);
    expect($unit->fresh()->register_uploaded_at)->not->toBeNull();
    Process::assertRan(fn (PendingProcess $process): bool => $process->command[1] === base_path('scripts/extract-register.mjs'));
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
});
