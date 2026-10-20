<?php

namespace App\Http\Controllers;

use App\Jobs\ImportVoterRegister;
use App\Models\CoordinatorAgent;
use App\Models\Lga;
use App\Models\PollingUnit;
use App\Models\Voter;
use App\Models\VoterRegisterImport;
use App\Models\Ward;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VoterImportController extends Controller
{
    public function create(Request $request): View
    {
        return view('voters.import', [
            'imports' => $this->visibleImports($request)->with('pollingUnit', 'user:id,name')->latest('id')->paginate(10),
            'lgas' => Lga::orderBy('name')->get(),
            'wards' => Ward::where('lga_id', $request->old('lga'))->orderBy('name')->get(),
            'pollingUnits' => PollingUnit::where('ward_id', $request->old('ward'))->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'lga' => ['required', 'integer', 'exists:lgas,id'],
            'ward' => ['required', 'integer', Rule::exists('wards', 'id')->where('lga_id', $request->input('lga'))],
            'polling_unit_id' => ['required', 'integer', Rule::exists('polling_units', 'id')->where('ward_id', $request->input('ward'))],
            'pdf' => 'required|file|mimes:pdf|max:51200',
        ]);

        $storedPath = $request->file('pdf')
            ->store('register-imports', 'local');
        if (! $storedPath) {
            throw ValidationException::withMessages(['pdf' => 'The file could not be stored. Please try again or contact support.']);
        }
        $lock = Cache::store('database')->lock('voter-import-enqueue', 30);
        try {
            $lock->block(3);
            DB::transaction(function () use ($request, $validated, $storedPath): void {
                PollingUnit::whereKey($validated['polling_unit_id'])->lockForUpdate()->firstOrFail();
                $this->assertImportCapacity((int) $validated['polling_unit_id'], $request->user()->id);
                $import = VoterRegisterImport::create([
                    'user_id' => $request->user()->id, 'polling_unit_id' => $validated['polling_unit_id'],
                    'original_name' => mb_substr($request->file('pdf')->getClientOriginalName(), 0, 255), 'pdf_path' => $storedPath,
                ]);
                ImportVoterRegister::dispatch($import->id)->beforeCommit();
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($storedPath);
            if ($exception instanceof LockTimeoutException) {
                throw ValidationException::withMessages(['pdf' => 'Uploads are busy. Please wait a moment and try again.']);
            }
            if ($exception instanceof ValidationException) {
                throw $exception;
            }
            report($exception);
            throw ValidationException::withMessages(['pdf' => 'The upload could not be queued. Please try again in a moment or contact support.']);
        } finally {
            $lock->release();
        }

        return redirect()->route('voters.import.create')->with('status', 'PDF uploaded and queued. You can upload another register while this one processes.');
    }

    public function importStatus(Request $request): JsonResponse
    {
        $imports = $this->visibleImports($request)->latest('id')->paginate(10)->through(fn (VoterRegisterImport $import): array => [
            'id' => $import->id, 'status' => $import->status, 'voters_count' => $import->voters_count, 'conflicts_count' => $import->conflicts_count,
            'error' => $import->error_message, 'can_retry' => $import->status === 'failed' && $import->pdf_path !== null,
        ]);

        return response()->json(['imports' => $imports->items()]);
    }

    public function retryImport(Request $request, VoterRegisterImport $import): RedirectResponse
    {
        abort_unless($request->user()->role === 'super_admin' || $import->user_id === $request->user()->id, 404);
        $lock = Cache::store('database')->lock('voter-import-enqueue', 30);
        try {
            $lock->block(3);
            DB::transaction(function () use ($import): void {
                PollingUnit::whereKey($import->polling_unit_id)->lockForUpdate()->firstOrFail();
                $record = VoterRegisterImport::whereKey($import->id)->lockForUpdate()->firstOrFail();
                if ($record->status !== 'failed' || ! $record->pdf_path || ! Storage::disk('local')->exists($record->pdf_path)) {
                    throw ValidationException::withMessages(['pdf' => 'This import cannot be retried. If its source has expired, upload the PDF again.']);
                }
                if (! $record->user?->hasPermission('import-voters')) {
                    throw ValidationException::withMessages(['pdf' => 'The uploader no longer has import access. Upload the PDF again using an active IT or super-admin account.']);
                }
                $this->assertImportCapacity($record->polling_unit_id, $record->user_id);
                $record->update(['status' => 'queued', 'error_message' => null, 'finished_at' => null]);
                ImportVoterRegister::dispatch($record->id)->beforeCommit();
            });
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages(['pdf' => 'Uploads are busy. Please wait a moment and try again.']);
        } finally {
            $lock->release();
        }

        return redirect()->route('voters.import.create')->with('status', 'Import queued for another attempt.');
    }

    private function visibleImports(Request $request): Builder
    {
        return VoterRegisterImport::query()->when($request->user()->role !== 'super_admin', fn (Builder $query) => $query->where('user_id', $request->user()->id));
    }

    private function assertImportCapacity(int $unitId, int $userId): void
    {
        if (VoterRegisterImport::where('polling_unit_id', $unitId)->whereIn('status', ['queued', 'processing'])->exists()) {
            throw ValidationException::withMessages(['polling_unit_id' => 'This polling unit already has an import queued or processing. Wait for it to finish before uploading again.']);
        }
        if (VoterRegisterImport::whereIn('status', ['queued', 'processing'])->count() >= max(1, (int) config('voter-imports.max_pending'))) {
            throw ValidationException::withMessages(['pdf' => 'The import queue is full. Wait for some imports to finish, then upload again.']);
        }
        if (VoterRegisterImport::where('user_id', $userId)->whereIn('status', ['queued', 'processing'])->count() >= max(1, (int) config('voter-imports.max_pending_per_user'))) {
            throw ValidationException::withMessages(['pdf' => 'You already have the maximum number of pending imports. Wait for one to finish before uploading another PDF.']);
        }
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'lga' => ['nullable', 'integer', 'exists:lgas,id'],
            'ward' => ['nullable', 'integer', Rule::exists('wards', 'id')->when($request->filled('lga'), fn (Exists $rule): Exists => $rule->where('lga_id', $request->input('lga')))],
            'polling_unit_id' => ['nullable', 'integer', Rule::exists('polling_units', 'id')->when($request->filled('ward'), fn (Exists $rule): Exists => $rule->where('ward_id', $request->input('ward')))],
            'q' => ['nullable', 'string', 'max:100'],
            'upload_status' => ['nullable', Rule::in(['uploaded', 'pending'])],
        ]);

        $units = PollingUnit::query();
        $coordinator = $request->user() instanceof CoordinatorAgent ? $request->user() : null;
        $assignedUnit = $coordinator?->membership?->puInfo;
        if ($coordinator) {
            $units->whereKey($coordinator->membership->pu);
        }
        if ($request->filled('lga')) {
            $units->whereHas('ward', fn (Builder $query) => $query->where('lga_id', $filters['lga']));
        }
        if ($request->filled('ward')) {
            $units->where('ward_id', $filters['ward']);
        }
        if ($request->filled('polling_unit_id')) {
            $units->whereKey($filters['polling_unit_id']);
        }

        $totalUnits = (clone $units)->count();
        $uploadedUnits = (clone $units)->where(fn (Builder $query) => $query->whereNotNull('register_uploaded_at')->orWhereHas('voters'))->count();

        $phoneProgress = null;
        $unitLeaderboard = collect();
        $coordinatorPoints = 0;
        $coordinatorRank = null;
        if ($coordinator) {
            $unitVoters = Voter::where('polling_unit_id', $coordinator->membership->pu);
            $unitTotal = (clone $unitVoters)->count();
            $missingPhones = (clone $unitVoters)->where(fn (Builder $query) => $query->whereNull('phone')->orWhere('phone', ''))->count();
            $phoneProgress = [
                'added' => $unitTotal - $missingPhones,
                'missing' => $missingPhones,
                'percent' => $unitTotal > 0 ? (int) round(($unitTotal - $missingPhones) / $unitTotal * 100) : 0,
            ];
            $unitLeaderboard = CoordinatorAgent::with('membership:id,name')
                ->whereHas('membership', fn (Builder $query) => $query->where('pu', $coordinator->membership->pu))
                ->withCount(['phoneCollections as points' => fn (Builder $query) => $query->where('polling_unit_id', $coordinator->membership->pu)])
                ->orderByDesc('points')->orderBy('id')->get();
            $coordinatorPoints = (int) $unitLeaderboard->firstWhere('id', $coordinator->id)?->points;
            $coordinatorRank = 1 + $unitLeaderboard->where('points', '>', $coordinatorPoints)->count();
        }

        $voters = Voter::with('pollingUnit', 'phoneAddedBy.membership');
        if ($coordinator) {
            $voters->where('polling_unit_id', $coordinator->membership->pu);
            $voters->where(fn (Builder $query) => $query->whereNull('phone')->orWhere('phone', ''));
        }
        if ($request->anyFilled(['lga', 'ward', 'polling_unit_id'])) {
            $voters->whereIn('polling_unit_id', (clone $units)->select('id'));
        }
        if ($request->filled('q')) {
            $search = '%'.trim($filters['q']).'%';
            $voters->where(function (Builder $query) use ($search, $coordinator): void {
                $query->where('full_name', 'like', $search);
                if (! $coordinator) {
                    $query->orWhere('vin', 'like', $search);
                }
            });
        }

        if (($filters['upload_status'] ?? null) === 'uploaded') {
            $units->where(fn (Builder $query) => $query->whereNotNull('register_uploaded_at')->orWhereHas('voters'));
        } elseif (($filters['upload_status'] ?? null) === 'pending') {
            $units->whereNull('register_uploaded_at')->whereDoesntHave('voters');
        }

        return view('voters.index', [
            'phoneProgress' => $phoneProgress,
            'unitLeaderboard' => $unitLeaderboard,
            'coordinatorPoints' => $coordinatorPoints,
            'coordinatorRank' => $coordinatorRank,
            'assignedUnit' => $assignedUnit,
            'coordinatorName' => $coordinator?->membership?->name,
            'voters' => $voters->orderBy('serial')->orderBy('id')->paginate(24)->withQueryString(),
            'unitStatuses' => $units->with('ward.lga')->withCount('voters')->orderBy('name')->orderBy('id')->paginate(6, ['*'], 'unit_page')->withQueryString(),
            'totalUnits' => $totalUnits,
            'uploadedUnits' => $uploadedUnits,
            'pendingUnits' => $totalUnits - $uploadedUnits,
            'lgas' => Lga::when($coordinator, fn (Builder $query) => $query->whereKey($assignedUnit?->ward?->lga_id))->orderBy('name')->get(),
            'wards' => Ward::where('lga_id', $filters['lga'] ?? $assignedUnit?->ward?->lga_id ?? 0)->when($coordinator, fn (Builder $query) => $query->whereKey($assignedUnit?->ward_id))->orderBy('name')->get(),
            'pollingUnits' => PollingUnit::where('ward_id', $filters['ward'] ?? $assignedUnit?->ward_id ?? 0)->when($coordinator, fn (Builder $query) => $query->whereKey($assignedUnit?->id))->orderBy('name')->get(),
        ]);
    }
}
