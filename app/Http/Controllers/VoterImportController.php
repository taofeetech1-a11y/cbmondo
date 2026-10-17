<?php

namespace App\Http\Controllers;

use App\Models\CoordinatorAgent;
use App\Models\Lga;
use App\Models\PollingUnit;
use App\Models\Voter;
use App\Models\Ward;
use App\Services\RegisterImporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\View\View;

class VoterImportController extends Controller
{
    public function create(Request $request): View
    {
        return view('voters.import', [
            'lgas' => Lga::orderBy('name')->get(),
            'wards' => Ward::where('lga_id', $request->old('lga'))->orderBy('name')->get(),
            'pollingUnits' => PollingUnit::where('ward_id', $request->old('ward'))->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, RegisterImporter $importer): RedirectResponse
    {
        set_time_limit(max(300, (int) config('services.voter_import_timeout', 600)));

        $validated = $request->validate([
            'lga' => ['required', 'integer', 'exists:lgas,id'],
            'ward' => ['required', 'integer', Rule::exists('wards', 'id')->where('lga_id', $request->input('lga'))],
            'polling_unit_id' => ['required', 'integer', Rule::exists('polling_units', 'id')->where('ward_id', $request->input('ward'))],
            'pdf' => 'required|file|mimes:pdf|max:51200',
        ]);

        $pollingUnit = PollingUnit::findOrFail($validated['polling_unit_id']);

        $storedPath = $request->file('pdf')
            ->store('register-imports', 'local');

        $pdfPath = Storage::disk('local')
            ->path($storedPath);

        try {
            $count = $importer->import($pdfPath, $pollingUnit);

            return redirect()
                ->route($request->user()->role === 'it' ? 'voters.import.create' : 'voters.index')
                ->with('status', "$count voters imported.");
        } finally {
            Storage::disk('local')
                ->delete($storedPath);
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
