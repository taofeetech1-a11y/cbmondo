<?php

namespace App\Services;

use App\Models\PollingUnit;
use App\Models\Voter;
use App\Models\VoterImportConflict;
use App\Models\VoterRegisterImport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\File as HttpFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class RegisterImporter
{
    public function import(string $pdfPath, PollingUnit $pollingUnit, ?int $importId = null): int
    {
        $work = storage_path('app/tmp/voter-import-'.Str::uuid());
        $photoDirectory = 'voters/'.Str::uuid();
        File::makeDirectory($work, 0755, true);
        $committed = false;
        $oldPhotos = [];
        try {
            $script = base_path('scripts/extract-register.mjs');
            if (! File::isFile($script) || ! File::isFile($pdfPath)) {
                throw new RuntimeException('The extraction script or source PDF is missing.');
            }
            $result = Process::path(base_path())->env([
                'NODE_OPTIONS' => trim((string) getenv('NODE_OPTIONS')).' --max-old-space-size='.max(128, (int) config('voter-imports.node_memory_mb', 256)),
                'UV_THREADPOOL_SIZE' => '1',
            ])->timeout(300)->run([config('services.node', 'node'), $script, $pdfPath, $work]);
            if ($result->failed()) {
                throw new RuntimeException('Extraction failed (exit code '.$result->exitCode().'): '.$result->errorOutput());
            }
            if (! File::isFile($work.'/records.json')) {
                throw new RuntimeException('Extraction completed without a records file.');
            }
            if ($result->errorOutput()) {
                Log::warning('Register extraction warnings', ['import_id' => $importId, 'warnings' => $result->errorOutput()]);
            }
            $data = json_decode(File::get($work.'/records.json'), true, 512, JSON_THROW_ON_ERROR);
            Validator::make($data, [
                'expected' => ['present', 'nullable', 'integer', 'min:0'],
                'delimitation_code' => ['nullable', 'string', 'max:30'],
                'records' => ['present', 'array'],
                'records.*.vin' => ['nullable', 'string', 'max:255', 'distinct:ignore_case'],
                'records.*.full_name' => ['nullable', 'string', 'max:255'],
                'records.*.surname' => ['nullable', 'string', 'max:255'],
                'records.*.other_names' => ['nullable', 'string', 'max:255'],
                'records.*.occupation' => ['nullable', 'string', 'max:255'],
                'records.*.gender' => ['nullable', 'in:Male,Female'],
                'records.*.age' => ['nullable', 'integer', 'between:0,255'],
                'records.*.serial' => ['nullable', 'integer', 'min:1'],
                'records.*.source_key' => ['nullable', 'string', 'max:255'],
                'records.*.photo' => ['nullable', 'string', 'regex:/^[A-Za-z0-9_-]+\.(jpg|jpeg|png|webp)$/i'],
            ])->validate();
            $pdfCode = $this->normalizeDelimitationCode($data['delimitation_code'] ?? null);
            $selectedCode = $this->normalizeDelimitationCode($pollingUnit->delimitation_code);
            if (filled($data['delimitation_code'] ?? null) && $pdfCode === null) {
                throw ValidationException::withMessages(['polling_unit_id' => 'The PDF delimitation code could not be read reliably. Check the code and selected polling unit.']);
            }
            if ($pdfCode !== null && $pdfCode !== $selectedCode) {
                throw ValidationException::withMessages(['polling_unit_id' => 'The PDF delimitation code ('.$pdfCode.') does not match the selected polling unit ('.($selectedCode ?? 'code not recorded').'). Select the polling unit with the matching code; its name may be different.']);
            }
            $records = $data['records'];
            if ($data['expected'] !== null && count($records) !== $data['expected']) {
                throw new RuntimeException('The extracted voter count does not match the PDF total.');
            }
            $pollingUnit->loadMissing('ward.lga');
            $header = $data['header'] ?? [
                'state' => null, 'lga' => $pollingUnit->ward->lga->name,
                'registration_area' => $pollingUnit->ward->name, 'polling_unit' => $pollingUnit->name,
            ];
            $rows = [];
            foreach ($records as $position => $record) {
                $photoPath = null;
                if ($record['photo'] ?? null) {
                    $photoPath = Storage::disk('local')->putFileAs($photoDirectory, new HttpFile($work.'/'.$record['photo']), $record['photo']);
                    if (! $photoPath) {
                        throw new RuntimeException('There is not enough storage to save the extracted photos.');
                    }
                }
                $rows[] = [
                    'id' => null,
                    'register_entry_key' => hash('sha256', $record['source_key'] ?? 'position:'.$position),
                    'vin' => $record['vin'] ?? null, 'serial' => $record['serial'] ?? null, 'full_name' => $record['full_name'] ?? null,
                    'surname' => $record['surname'] ?? null, 'other_names' => $record['other_names'] ?? null,
                    'occupation' => $record['occupation'] ?? null, 'gender' => $record['gender'] ?? null, 'age' => $record['age'] ?? null,
                    'photo_path' => $photoPath, 'state' => $header['state'] ?? null, 'lga' => $header['lga'] ?? null,
                    'registration_area' => $header['registration_area'] ?? null, 'polling_unit' => $header['polling_unit'] ?? null,
                    'polling_unit_id' => $pollingUnit->id, 'created_at' => now(), 'updated_at' => now(),
                ];
            }
            if (count(array_unique(array_column($rows, 'register_entry_key'))) !== count($rows)) {
                throw new RuntimeException('The PDF contains duplicate record positions.');
            }
            usort($rows, fn (array $left, array $right): int => strcmp($left['vin'] ?? '', $right['vin'] ?? ''));
            $importedCount = DB::transaction(function () use ($rows, $pollingUnit, $importId, $photoDirectory, &$oldPhotos): int {
                $importedCount = 0;
                $conflictsCount = 0;
                PollingUnit::whereKey($pollingUnit->id)->lockForUpdate()->firstOrFail();
                foreach (array_chunk($rows, 200) as $batch) {
                    $conflicts = [];
                    Voter::insertOrIgnore($batch);
                    $existing = Voter::where(function (Builder $query) use ($batch, $pollingUnit): void {
                        $query->whereIn('vin', array_filter(array_column($batch, 'vin')))
                            ->orWhere(fn (Builder $query): Builder => $query->where('polling_unit_id', $pollingUnit->id)->whereIn('register_entry_key', array_column($batch, 'register_entry_key')));
                    })->orderBy('id')->lockForUpdate()->get();
                    foreach ($batch as $index => $row) {
                        $byVin = $row['vin'] === null ? null : $existing->first(fn (Voter $voter): bool => strcasecmp($voter->vin ?? '', $row['vin']) === 0);
                        $byEntry = $existing->first(fn (Voter $voter): bool => (int) $voter->polling_unit_id === (int) $pollingUnit->id && $voter->register_entry_key === $row['register_entry_key']);
                        $voter = $byVin ?? $byEntry;
                        $reason = null;
                        if ($byVin && $byVin->polling_unit_id !== null && (int) $byVin->polling_unit_id !== (int) $pollingUnit->id) {
                            $reason = 'vin_in_another_unit';
                        } elseif (($byEntry && $byVin && $byEntry->id !== $byVin->id)
                            || ($voter?->vin && $row['vin'] && strcasecmp($voter->vin, $row['vin']) !== 0)) {
                            $reason = 'record_position_conflict';
                        }
                        if ($reason !== null) {
                            $conflictingVoter = $reason === 'record_position_conflict' ? ($byEntry ?? $voter) : $voter;
                            $conflicts[] = [
                                'voter_register_import_id' => $importId, 'polling_unit_id' => $pollingUnit->id,
                                'existing_voter_id' => $conflictingVoter->id, 'existing_polling_unit_id' => $conflictingVoter->polling_unit_id,
                                'record_key' => $row['register_entry_key'], 'vin' => $row['vin'], 'reason' => $reason,
                                'record' => json_encode(Arr::except($row, ['id', 'created_at', 'updated_at', 'photo_path']), JSON_THROW_ON_ERROR),
                                'photo_path' => $row['photo_path'], 'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
                            ];
                            unset($batch[$index]);

                            continue;
                        }
                        $batch[$index]['id'] = $voter?->id;
                        if ($voter?->photo_path && ! str_starts_with($voter->photo_path, $photoDirectory.'/')) {
                            $oldPhotos[] = $voter->photo_path;
                        }
                    }
                    if ($conflicts !== []) {
                        VoterImportConflict::insert($conflicts);
                        $conflictsCount += count($conflicts);
                    }
                    $importedCount += count($batch);
                    if ($batch !== []) {
                        Voter::upsert(array_values($batch), ['id'], [
                            'vin', 'register_entry_key', 'serial', 'full_name', 'surname', 'other_names', 'occupation', 'gender', 'age',
                            'photo_path', 'state', 'lga', 'registration_area', 'polling_unit', 'polling_unit_id', 'updated_at',
                        ]);
                    }
                }
                $pollingUnit->forceFill(['register_uploaded_at' => now()])->save();
                if ($importId !== null) {
                    VoterRegisterImport::whereKey($importId)->update([
                        'status' => 'completed', 'voters_count' => $importedCount, 'conflicts_count' => $conflictsCount, 'finished_at' => now(),
                    ]);
                }

                return $importedCount;
            }, 3);
            $committed = true;
            foreach (array_chunk(array_values(array_unique($oldPhotos)), 200) as $paths) {
                $referenced = Voter::whereIn('photo_path', $paths)->pluck('photo_path')->all();
                $referenced = array_merge($referenced, VoterImportConflict::whereIn('photo_path', $paths)->pluck('photo_path')->all());
                Storage::disk('local')->delete(array_values(array_filter(array_diff($paths, $referenced), fn (string $path): bool => str_starts_with($path, 'voters/'))));
            }

            return $importedCount;
        } catch (Throwable $exception) {
            if (! $committed) {
                Storage::disk('local')->deleteDirectory($photoDirectory);
            }
            throw $exception;
        } finally {
            File::deleteDirectory($work);
        }
    }

    private function normalizeDelimitationCode(?string $code): ?string
    {
        if ($code === null || ! preg_match('/\A\s*(\d{1,2})\s*[-\/–—]\s*(\d{1,2})\s*[-\/–—]\s*(\d{1,2})\s*[-\/–—]\s*(\d{1,3})\s*\z/u', $code, $parts)) {
            return null;
        }

        return sprintf('%02d/%02d/%02d/%03d', (int) $parts[1], (int) $parts[2], (int) $parts[3], (int) $parts[4]);
    }
}
