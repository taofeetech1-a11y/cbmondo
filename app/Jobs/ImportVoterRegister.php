<?php

namespace App\Jobs;

use App\Models\VoterRegisterImport;
use App\Services\RegisterImporter;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ImportVoterRegister implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public int $timeout = 660;

    public int $maxExceptions = 3;

    public bool $failOnTimeout = true;

    public bool $countCrashesAsExceptions = true;

    public int $retryDeadline;

    public function __construct(public int $importId)
    {
        $this->onConnection('voter-imports')->onQueue('voter-imports');
        $this->retryDeadline = now()->addWeek()->timestamp;
    }

    /**
     * Execute the job.
     */
    public function handle(RegisterImporter $importer): void
    {
        $lock = Cache::store('database')->lock('voter-import-processing', 720);
        if (! $lock->get()) {
            $this->release(30);

            return;
        }
        try {
            $import = VoterRegisterImport::find($this->importId);
            if (! $import || in_array($import->status, ['completed', 'failed'], true)) {
                if ($import?->status === 'completed' && $import->pdf_path && Storage::disk('local')->delete($import->pdf_path)) {
                    $import->update(['pdf_path' => null]);
                }

                return;
            }
            $import->update(['status' => 'processing', 'started_at' => now(), 'error_message' => null, 'attempts' => $import->attempts + 1]);
            try {
                if (! $import->user?->hasPermission('import-voters')) {
                    throw new \RuntimeException('The uploader no longer has permission to import voters.');
                }
                if (! $import->pdf_path || ! Storage::disk('local')->exists($import->pdf_path)) {
                    throw new \RuntimeException('The private source PDF is missing.');
                }
                set_time_limit(660);
                $count = $importer->import(Storage::disk('local')->path($import->pdf_path), $import->pollingUnit, $import->id);
                $import->update(['status' => 'completed', 'voters_count' => $count, 'finished_at' => now()]);
                if (Storage::disk('local')->delete($import->pdf_path)) {
                    $import->update(['pdf_path' => null]);
                }
            } catch (QueryException $exception) {
                VoterRegisterImport::whereKey($import->id)->where('status', '!=', 'completed')->update(['status' => 'queued']);
                throw $exception;
            } catch (Throwable $exception) {
                if ($this->job) {
                    $this->fail($exception);
                } else {
                    $this->failed($exception);
                }
            }
        } finally {
            $lock->release();
        }
    }

    public function retryUntil(): DateTimeInterface
    {
        return Carbon::createFromTimestamp($this->retryDeadline);
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [60, 180, 600];
    }

    public function failed(?Throwable $exception): void
    {
        VoterRegisterImport::whereKey($this->importId)->where('status', '!=', 'completed')->update([
            'status' => 'failed', 'finished_at' => now(),
            'error_message' => 'Import could not finish. Check the PDF and selected unit, then retry or upload a corrected file. Contact IT support with this import ID if it continues.',
        ]);
        Log::error('Voter import failed', ['import_id' => $this->importId, 'exception' => $exception]);
    }
}
