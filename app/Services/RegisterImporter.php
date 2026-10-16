<?php

namespace App\Services;

use App\Models\PollingUnit;
use App\Models\Voter;
use Illuminate\Http\File as HttpFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class RegisterImporter
{
    // public function import(string $pdfPath): int
    // {
    //     $work = storage_path('app/tmp/' . Str::uuid());
    //     File::makeDirectory($work, 0755, true);

    //     try {

    //         $result = Process::path(base_path())
    //             ->timeout(300)
    //             ->run([
    //                 config('services.node'),
    //                 base_path('scripts/extract-register.mjs'),
    //                 $pdfPath,
    //                 $work,
    //             ]);

    //         if ($result->failed()) {
    //             throw new RuntimeException('Extraction failed: ' . $result->errorOutput());
    //         }
    //         if ($result->errorOutput()) {
    //             Log::warning('Register extraction warnings: ' . $result->errorOutput());
    //         }

    //         $data    = json_decode(File::get("$work/records.json"), true);
    //         $header  = $data['header'];
    //         $records = $data['records'];

    //         if ($data['expected'] && count($records) !== $data['expected']) {
    //             throw new RuntimeException(
    //                 "Expected {$data['expected']} records but parsed " . count($records)
    //             );
    //         }

    //         DB::transaction(function () use ($records, $header, $work) {
    //             foreach ($records as $r) {
    //                 $photoPath = null;
    //                 if ($r['photo']) {
    //                     $photoPath = Storage::disk('public')->putFileAs(
    //                         'voters',
    //                         new HttpFile("$work/{$r['photo']}"),
    //                         $r['photo']
    //                     );
    //                 }

    //                 Voter::updateOrCreate(['vin' => $r['vin']], [
    //                     'serial'            => $r['serial'],
    //                     'full_name'         => $r['full_name'],
    //                     'surname'           => $r['surname'],
    //                     'other_names'       => $r['other_names'],
    //                     'occupation'        => $r['occupation'],
    //                     'gender'            => $r['gender'],
    //                     'age'               => $r['age'],
    //                     'photo_path'        => $photoPath,
    //                     'state'             => $header['state'],
    //                     'lga'               => $header['lga'],
    //                     'registration_area' => $header['registration_area'],
    //                     'polling_unit'      => $header['polling_unit'],
    //                 ]);
    //             }
    //         });

    //         return count($records);
    //     } finally {
    //         File::deleteDirectory($work);
    //     }
    // }

    public function import(string $pdfPath, PollingUnit $pollingUnit): int
    {
        $work = storage_path('app/tmp/'.Str::uuid());
        File::makeDirectory($work, 0755, true);

        try {

            $node = config('services.node', 'node');
            $script = base_path('scripts/extract-register.mjs');

            // Check that the JavaScript file exists
            if (! File::isFile($script)) {
                throw new RuntimeException(
                    "Extraction script not found: {$script}"
                );
            }

            // Check that the PDF exists
            if (! File::isFile($pdfPath)) {
                throw new RuntimeException(
                    "PDF file not found: {$pdfPath}"
                );
            }

            // Run the Node.js extraction script
            try {
                $result = Process::path(base_path())
                    ->timeout(300)
                    ->run([
                        $node,
                        $script,
                        $pdfPath,
                        $work,
                    ]);
            } catch (\Throwable $e) {
                throw new RuntimeException(
                    'Unable to start Node.js: '.$e->getMessage(),
                    0,
                    $e
                );
            }

            // Show detailed error if extraction fails
            if ($result->failed()) {
                throw new RuntimeException(
                    'Extraction failed (exit code '
                        .$result->exitCode()
                        .'): '
                        .$result->errorOutput()
                        ."\n"
                        .$result->output()
                );
            }

            // Check that the JSON output was created
            if (! File::isFile("$work/records.json")) {
                throw new RuntimeException(
                    'Extraction completed but records.json was not created.'
                );
            }
            if ($result->errorOutput()) {
                Log::warning('Register extraction warnings: '.$result->errorOutput());
            }

            $data = json_decode(File::get("$work/records.json"), true);
            $pollingUnit->loadMissing('ward.lga');
            $header = $data['header'] ?? [
                'state' => null,
                'lga' => $pollingUnit->ward->lga->name,
                'registration_area' => $pollingUnit->ward->name,
                'polling_unit' => $pollingUnit->name,
            ];
            $records = $data['records'];

            if ($data['expected'] !== null && count($records) !== $data['expected']) {
                throw new RuntimeException(
                    "Expected {$data['expected']} records but parsed ".count($records)
                );
            }

            DB::transaction(function () use ($records, $header, $work, $pollingUnit) {
                foreach ($records as $r) {
                    $photoPath = null;
                    if ($r['photo']) {
                        $photoPath = Storage::disk('public')->putFileAs(
                            'voters',
                            new HttpFile("$work/{$r['photo']}"),
                            $r['photo']
                        );
                    }

                    Voter::updateOrCreate(['vin' => $r['vin']], [
                        'serial' => $r['serial'],
                        'full_name' => $r['full_name'],
                        'surname' => $r['surname'],
                        'other_names' => $r['other_names'],
                        'occupation' => $r['occupation'],
                        'gender' => $r['gender'],
                        'age' => $r['age'],
                        'photo_path' => $photoPath,
                        'state' => $header['state'],
                        'lga' => $header['lga'],
                        'registration_area' => $header['registration_area'],
                        'polling_unit' => $header['polling_unit'],
                        'polling_unit_id' => $pollingUnit->id,
                    ]);
                }

                $pollingUnit->register_uploaded_at = now();
                $pollingUnit->save();
            });

            return count($records);
        } finally {
            File::deleteDirectory($work);
        }
    }
}
