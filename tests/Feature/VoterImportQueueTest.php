<?php

use App\Jobs\ImportVoterRegister;
use App\Models\CoordinatorAgent;
use App\Models\PollingUnit;
use App\Models\User;
use App\Models\Voter;
use App\Models\VoterRegisterImport;
use App\Services\RegisterImporter;
use Illuminate\Http\UploadedFile;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

function queuedPdfData(PollingUnit $unit): array
{
    return ['lga' => $unit->ward->lga_id, 'ward' => $unit->ward_id, 'polling_unit_id' => $unit->id,
        'pdf' => UploadedFile::fake()->create('register.pdf', 1, 'application/pdf')];
}

function queuedVoterRecord(string $vin = 'AAAA BBBB CCCC DDDD 123'): array
{
    return ['serial' => 1, 'vin' => $vin, 'full_name' => 'Example Voter', 'surname' => 'Voter',
        'other_names' => 'Example', 'occupation' => null, 'gender' => 'Male', 'age' => 30, 'photo' => 'private.jpg'];
}

function fakeQueuedExtraction(array $records): void
{
    Process::fake(function (PendingProcess $process) use ($records) {
        foreach ($records as $record) {
            if ($record['photo'] ?? null) {
                File::put($process->command[3].'/'.$record['photo'], 'private photo');
            }
        }
        File::put($process->command[3].'/records.json', json_encode([
            'header' => null, 'expected' => count($records), 'records' => $records,
        ], JSON_THROW_ON_ERROR));

        return Process::result();
    });
}

it('accepts independent staff uploads without extraction and prevents overlapping uploads for one unit', function () {
    Storage::fake('local');
    Process::fake();
    $first = User::factory()->create(['role' => 'it']);
    $second = User::factory()->create(['role' => 'it']);
    $units = PollingUnit::factory()->count(2)->create();
    $this->actingAs($first, 'web')->post(route('voters.import'), queuedPdfData($units[0]))
        ->assertRedirect(route('voters.import.create'))->assertSessionHasNoErrors();
    $this->actingAs($second, 'web')->post(route('voters.import'), queuedPdfData($units[1]))->assertSessionHasNoErrors();
    $this->post(route('voters.import'), queuedPdfData($units[0]))->assertSessionHasErrors('polling_unit_id');
    $this->assertDatabaseCount('voter_register_imports', 2);
    $this->assertDatabaseCount('jobs', 2);
    expect(Storage::disk('local')->files('register-imports'))->toHaveCount(2);
    Process::assertNothingRan();
});

it('bounds queue capacity and removes rejected private uploads', function () {
    Storage::fake('local');
    Queue::fake();
    config(['voter-imports.max_pending' => 1]);
    $units = PollingUnit::factory()->count(2)->create();
    $this->actingAs(User::factory()->create(['role' => 'it']), 'web')
        ->post(route('voters.import'), queuedPdfData($units[0]))->assertSessionHasNoErrors();
    $this->post(route('voters.import'), queuedPdfData($units[1]))->assertSessionHasErrors('pdf');
    expect(Storage::disk('local')->files('register-imports'))->toHaveCount(1);
    Queue::assertPushed(ImportVoterRegister::class, 1);
});

it('limits one uploaders backlog while allowing other staff to keep uploading', function () {
    Storage::fake('local');
    Queue::fake();
    config(['voter-imports.max_pending_per_user' => 1]);
    $units = PollingUnit::factory()->count(3)->create();
    $this->actingAs(User::factory()->create(['role' => 'it']), 'web')
        ->post(route('voters.import'), queuedPdfData($units[0]))->assertSessionHasNoErrors();
    $this->post(route('voters.import'), queuedPdfData($units[1]))->assertSessionHasErrors('pdf');
    $this->actingAs(User::factory()->create(['role' => 'it']), 'web')
        ->post(route('voters.import'), queuedPdfData($units[2]))->assertSessionHasNoErrors();
    $this->assertDatabaseCount('voter_register_imports', 2);
});

it('rolls back the import record and deletes the source when enqueueing fails', function () {
    Storage::fake('local');
    config(['queue.connections.voter-imports.table' => 'missing_queue_table']);
    $unit = PollingUnit::factory()->create();
    $this->actingAs(User::factory()->create(['role' => 'it']), 'web')
        ->post(route('voters.import'), queuedPdfData($unit))->assertSessionHasErrors('pdf');
    $this->assertDatabaseCount('voter_register_imports', 0);
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
});

it('uses the persistent database queue and completes atomically when a real worker executes the job', function () {
    Storage::fake('local');
    fakeQueuedExtraction([queuedVoterRecord()]);
    $processes = Process::getFacadeRoot();
    $unit = PollingUnit::factory()->create();
    $this->actingAs(User::factory()->create(['role' => 'it']), 'web')
        ->post(route('voters.import'), queuedPdfData($unit))->assertSessionHasNoErrors();
    $import = VoterRegisterImport::sole();
    expect($import->status)->toBe('queued');
    expect($unit->fresh()->register_uploaded_at)->toBeNull();
    Storage::disk('local')->assertExists($import->pdf_path);
    Artisan::call('queue:work', ['connection' => 'voter-imports', '--queue' => 'voter-imports', '--once' => true, '--timeout' => 660]);
    expect($import->fresh()->status)->toBe('completed');
    expect($import->fresh()->voters_count)->toBe(1);
    expect($import->fresh()->pdf_path)->toBeNull();
    expect($unit->fresh()->register_uploaded_at)->not->toBeNull();
    Storage::disk('local')->assertExists(Voter::sole()->photo_path);
    ImportVoterRegister::dispatch($import->id);
    Artisan::call('queue:work', ['connection' => 'voter-imports', '--queue' => 'voter-imports', '--once' => true]);
    $this->assertDatabaseCount('voters', 1);
    $processes->assertRanTimes(fn (PendingProcess $process): bool => $process->command[1] === base_path('scripts/extract-register.mjs'), 1);
});

it('releases competing processing jobs without starting a second extractor', function () {
    Process::fake();
    $lock = Cache::store('database')->lock('voter-import-processing', 720);
    $lock->get();
    try {
        $job = (new ImportVoterRegister(999))->withFakeQueueInteractions();
        $job->handle(app(RegisterImporter::class));
        $job->assertReleased(30);
        Process::assertNothingRan();
    } finally {
        $lock->release();
    }
});

it('prevents overlapping cPanel workers', function () {
    $lock = Cache::store('database')->lock('voter-import-worker', 1000);
    $lock->get();
    try {
        $this->artisan('voters:work-imports')->expectsOutput('An import worker is already running.')->assertSuccessful();
    } finally {
        $lock->release();
    }
});

it('keeps import status and retries private to the uploader and super admins', function () {
    Queue::fake();
    Storage::fake('local');
    $own = VoterRegisterImport::factory()->create(['status' => 'failed', 'finished_at' => now()]);
    $other = VoterRegisterImport::factory()->create(['status' => 'failed', 'finished_at' => now()]);
    Storage::disk('local')->put($own->pdf_path, 'pdf');
    Storage::disk('local')->put($other->pdf_path, 'pdf');
    $this->actingAs($own->user, 'web')->getJson(route('voters.imports.status'))
        ->assertOk()->assertJsonCount(1, 'imports')->assertJsonPath('imports.0.id', $own->id)->assertDontSee($own->pdf_path);
    $this->post(route('voters.imports.retry', $other))->assertNotFound();
    $this->post(route('voters.imports.retry', $own))->assertSessionHasNoErrors();
    expect($own->fresh()->status)->toBe('queued');
    $this->actingAs(User::factory()->superAdmin()->create(), 'web')->getJson(route('voters.imports.status'))
        ->assertJsonCount(2, 'imports');
    $this->actingAs(User::factory()->create(), 'web')->getJson(route('voters.imports.status'))->assertForbidden();
});

it('renders the uploaders history and retry controls without exposing private file paths or other staff uploads', function () {
    $own = VoterRegisterImport::factory()->create(['original_name' => 'own-register.pdf', 'status' => 'failed', 'error_message' => 'Check the PDF and retry.']);
    VoterRegisterImport::factory()->create(['original_name' => 'other-staff-register.pdf']);
    $this->actingAs($own->user, 'web')->get(route('voters.import.create'))->assertOk()
        ->assertSee('own-register.pdf')->assertSee('Retry import')->assertSee('Check the PDF and retry.')
        ->assertDontSee('other-staff-register.pdf')->assertDontSee($own->pdf_path);
});

it('retains failed PDFs for retry without exposing extraction details or partial records', function () {
    Storage::fake('local');
    $import = VoterRegisterImport::factory()->create();
    Storage::disk('local')->put($import->pdf_path, 'pdf');
    Process::fake(fn () => Process::result(errorOutput: 'private-system-path-and-vin', exitCode: 1));
    (new ImportVoterRegister($import->id))->handle(app(RegisterImporter::class));
    expect($import->fresh()->status)->toBe('failed');
    expect($import->fresh()->error_message)->not->toContain('private-system-path-and-vin');
    Storage::disk('local')->assertExists($import->pdf_path);
    $this->assertDatabaseCount('voters', 0);
    expect($import->pollingUnit->register_uploaded_at)->toBeNull();
    fakeQueuedExtraction([queuedVoterRecord()]);
    Queue::fake();
    $this->actingAs($import->user, 'web')->post(route('voters.imports.retry', $import))->assertSessionHasNoErrors();
    (new ImportVoterRegister($import->id))->handle(app(RegisterImporter::class));
    expect($import->fresh()->status)->toBe('completed');
});

it('does not transfer existing VINs between units or leave partial rows and photos on failure', function () {
    Storage::fake('local');
    $existing = Voter::factory()->for(PollingUnit::factory(), 'pollingUnit')->create(['phone' => '08012345678']);
    $import = VoterRegisterImport::factory()->create();
    Storage::disk('local')->put($import->pdf_path, 'pdf');
    fakeQueuedExtraction([queuedVoterRecord($existing->vin), queuedVoterRecord('ZZZZ BBBB CCCC DDDD 123')]);
    (new ImportVoterRegister($import->id))->handle(app(RegisterImporter::class));
    expect($import->fresh()->status)->toBe('failed');
    $this->assertDatabaseCount('voters', 1);
    expect($existing->fresh()->phone)->toBe('08012345678');
    expect($existing->fresh()->polling_unit_id)->not->toBe($import->polling_unit_id);
    expect(Storage::disk('local')->allFiles())->toBe([$import->pdf_path]);
});

it('imports incomplete voters as null and reimports without losing phone collection credit', function () {
    Storage::fake('local');
    $unit = PollingUnit::factory()->create();
    $staff = User::factory()->create(['role' => 'it']);
    $collector = CoordinatorAgent::factory()->create();
    $records = [
        ['source_key' => 'page:12:record:1', 'vin' => null, 'serial' => 386, 'photo' => null],
        ['source_key' => 'page:15:record:1', 'vin' => null, 'serial' => null, 'photo' => null],
    ];
    for ($attempt = 0; $attempt < 3; $attempt++) {
        $import = VoterRegisterImport::factory()->create(['polling_unit_id' => $unit->id, 'user_id' => $staff->id]);
        Storage::disk('local')->put($import->pdf_path, 'pdf');
        fakeQueuedExtraction($records);

        (new ImportVoterRegister($import->id))->handle(app(RegisterImporter::class));

        expect($import->fresh()->status)->toBe('completed');
        expect($import->fresh()->voters_count)->toBe(2);
        $this->assertDatabaseCount('voters', 2);
        if ($attempt === 0) {
            $voter = Voter::orderBy('id')->first();
            $this->assertDatabaseHas('voters', ['id' => $voter->id, 'vin' => null, 'full_name' => null,
                'surname' => null, 'other_names' => null, 'gender' => null, 'age' => null, 'occupation' => null, 'photo_path' => null]);
            $voter->forceFill(['phone' => '08012345678', 'phone_added_by' => $collector->id, 'phone_added_at' => now()])->save();
            $records[0]['vin'] = '90F5AF7A59466848074';
            $records[0]['full_name'] = 'Recovered Name';
        }
    }
    $this->assertDatabaseHas('voters', ['id' => $voter->id, 'vin' => '90F5AF7A59466848074',
        'full_name' => 'Recovered Name', 'phone' => '08012345678', 'phone_added_by' => $collector->id]);
    expect($voter->fresh()->phone_added_at)->not->toBeNull();
    $this->assertDatabaseHas('voters', ['polling_unit_id' => $unit->id, 'vin' => null, 'serial' => null]);
});

it('keeps voters without VINs separate across polling units even at the same PDF position', function () {
    Storage::fake('local');
    foreach (PollingUnit::factory()->count(2)->create() as $unit) {
        $import = VoterRegisterImport::factory()->create(['polling_unit_id' => $unit->id]);
        Storage::disk('local')->put($import->pdf_path, 'pdf');
        fakeQueuedExtraction([['source_key' => 'page:1:record:1']]);

        (new ImportVoterRegister($import->id))->handle(app(RegisterImporter::class));

        expect($import->fresh()->status)->toBe('completed');
        $this->assertDatabaseHas('voters', ['polling_unit_id' => $unit->id, 'vin' => null]);
    }
    $this->assertDatabaseCount('voters', 2);
});

it('does not replace a different identified voter occupying the same source position', function () {
    Storage::fake('local');
    $unit = PollingUnit::factory()->create();
    foreach (['90F5AF7A59466848074', 'INC22000000012524244'] as $index => $vin) {
        $import = VoterRegisterImport::factory()->create(['polling_unit_id' => $unit->id]);
        Storage::disk('local')->put($import->pdf_path, 'pdf');
        fakeQueuedExtraction([queuedVoterRecord($vin)]);

        (new ImportVoterRegister($import->id))->handle(app(RegisterImporter::class));

        expect($import->fresh()->status)->toBe($index === 0 ? 'completed' : 'failed');
    }
    $this->assertDatabaseCount('voters', 1);
    $this->assertDatabaseHas('voters', ['vin' => '90F5AF7A59466848074']);
});

it('renders incomplete voter names and demographics without inventing personal information', function () {
    $voter = Voter::factory()->for(PollingUnit::factory(), 'pollingUnit')->create([
        'vin' => null, 'full_name' => null, 'surname' => null, 'gender' => null, 'age' => null, 'photo_path' => null,
    ]);

    $this->actingAs(User::factory()->superAdmin()->create(), 'web')->get(route('voters.index'))
        ->assertOk()->assertSee('Name not recorded')->assertSee('Age not recorded')->assertSee('Gender not recorded');
    $this->get(route('voters.edit', $voter))->assertOk()->assertSee('Name not recorded');
});

it('isolates photo paths between PDFs and preserves collected phones and completion credit on reimport', function () {
    Storage::fake('local');
    $first = VoterRegisterImport::factory()->create();
    $second = VoterRegisterImport::factory()->create();
    foreach ([$first, $second] as $index => $import) {
        Storage::disk('local')->put($import->pdf_path, 'pdf');
        fakeQueuedExtraction([queuedVoterRecord('AAAA BBBB CCCC DDDD '.($index ? '456' : '123'))]);
        (new ImportVoterRegister($import->id))->handle(app(RegisterImporter::class));
    }
    $voters = Voter::orderBy('id')->get();
    expect($voters[0]->photo_path)->not->toBe($voters[1]->photo_path);
    $collector = CoordinatorAgent::factory()->create();
    $voters[0]->forceFill(['phone' => '08012345678', 'phone_added_by' => $collector->id, 'phone_added_at' => now()])->save();
    $retry = VoterRegisterImport::factory()->create(['polling_unit_id' => $first->polling_unit_id]);
    Storage::disk('local')->put($retry->pdf_path, 'pdf');
    fakeQueuedExtraction([queuedVoterRecord()]);
    (new ImportVoterRegister($retry->id))->handle(app(RegisterImporter::class));
    expect($voters[0]->fresh()->phone)->toBe('08012345678');
    expect($voters[0]->fresh()->phone_added_by)->toBe($collector->id);
    Storage::disk('local')->assertMissing($voters[0]->photo_path);
    Storage::disk('local')->assertExists($voters[1]->photo_path);
});

it('purges expired failed sources and completed leftover sources without removing history', function () {
    Storage::fake('local');
    $old = VoterRegisterImport::factory()->create(['status' => 'failed', 'finished_at' => now()->subDays(8)]);
    $recent = VoterRegisterImport::factory()->create(['status' => 'failed', 'finished_at' => now()]);
    $complete = VoterRegisterImport::factory()->create(['status' => 'completed']);
    foreach ([$old, $recent, $complete] as $import) {
        Storage::disk('local')->put($import->pdf_path, 'pdf');
    }
    $this->artisan('voters:work-imports')->assertSuccessful();
    expect($old->fresh()->pdf_path)->toBeNull();
    expect($complete->fresh()->pdf_path)->toBeNull();
    Storage::disk('local')->assertExists($recent->pdf_path);
    $this->assertDatabaseCount('voter_register_imports', 3);
});

it('does not process uploads after the uploader loses import permission', function () {
    Storage::fake('local');
    Process::fake();
    $import = VoterRegisterImport::factory()->create();
    Storage::disk('local')->put($import->pdf_path, 'pdf');
    $import->user->forceFill(['is_active' => false])->save();
    (new ImportVoterRegister($import->id))->handle(app(RegisterImporter::class));
    expect($import->fresh()->status)->toBe('failed');
    Process::assertNothingRan();
});

it('imports a supplied sample PDF through the real worker and stores its photos privately', function () {
    Storage::fake('local');
    $unit = PollingUnit::factory()->create();
    $sample = getenv('VOTER_IMPORT_SAMPLE_PDF');
    $data = queuedPdfData($unit);
    $data['pdf'] = new UploadedFile($sample, 'sample.pdf', 'application/pdf', null, true);
    $this->actingAs(User::factory()->create(['role' => 'it']), 'web')->post(route('voters.import'), $data)
        ->assertSessionHasNoErrors();
    Artisan::call('queue:work', ['connection' => 'voter-imports', '--queue' => 'voter-imports', '--once' => true, '--timeout' => 660]);
    $import = VoterRegisterImport::sole();
    expect($import->status)->toBe('completed');
    expect($import->voters_count)->toBeGreaterThan(0);
    expect(Voter::count())->toBe($import->voters_count);
    if (getenv('VOTER_IMPORT_SAMPLE_EXPECT_MISSING_VIN')) {
        expect(Voter::whereNull('vin')->exists())->toBeTrue();
    }
    foreach (Voter::all() as $voter) {
        if ($voter->photo_path !== null) {
            Storage::disk('local')->assertExists($voter->photo_path);
        }
    }
})->skip(! getenv('VOTER_IMPORT_SAMPLE_PDF'), 'Set VOTER_IMPORT_SAMPLE_PDF to run real PDF extraction.');
