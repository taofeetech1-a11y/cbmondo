<?php

namespace App\Console\Commands;

use App\Models\VoterRegisterImport;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

#[Signature('voters:work-imports')]
#[Description('Process private PDF imports with one bounded worker; safe to run every minute from cPanel cron')]
class WorkVoterImports extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $lock = Cache::store('database')->lock('voter-import-worker', 1000);
        if (! $lock->get()) {
            $this->info('An import worker is already running.');

            return self::SUCCESS;
        }
        try {
            VoterRegisterImport::whereNotNull('pdf_path')->where(function ($query): void {
                $query->where('status', 'completed')->orWhere(function ($failed): void {
                    $failed->where('status', 'failed')->where('finished_at', '<', now()->subDays(max(1, config('voter-imports.retention_days'))));
                });
            })
                ->chunkById(50, function ($imports): void {
                    foreach ($imports as $import) {
                        if (Storage::disk('local')->delete($import->pdf_path)) {
                            $import->update(['pdf_path' => null]);
                        }
                    }
                });
            $directories = File::isDirectory(storage_path('app/tmp')) ? File::directories(storage_path('app/tmp')) : [];
            foreach ($directories as $directory) {
                if (str_starts_with(basename($directory), 'voter-import-') && File::lastModified($directory) < now()->subDay()->timestamp) {
                    File::deleteDirectory($directory);
                }
            }

            return $this->call('queue:work', [
                'connection' => 'voter-imports', '--queue' => 'voter-imports',
                '--stop-when-empty' => true, '--max-time' => 240, '--timeout' => 660,
                '--memory' => 256, '--tries' => 3, '--sleep' => 1,
            ]);
        } finally {
            $lock->release();
        }
    }
}
