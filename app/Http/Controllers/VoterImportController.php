<?php

namespace App\Http\Controllers;

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
                ->route('voters.index')
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

        $voters = Voter::with('pollingUnit');
        if ($request->anyFilled(['lga', 'ward', 'polling_unit_id'])) {
            $voters->whereIn('polling_unit_id', (clone $units)->select('id'));
        }
        if ($request->filled('q')) {
            $search = '%'.trim($filters['q']).'%';
            $voters->where(fn (Builder $query) => $query->where('full_name', 'like', $search)->orWhere('vin', 'like', $search));
        }

        if (($filters['upload_status'] ?? null) === 'uploaded') {
            $units->where(fn (Builder $query) => $query->whereNotNull('register_uploaded_at')->orWhereHas('voters'));
        } elseif (($filters['upload_status'] ?? null) === 'pending') {
            $units->whereNull('register_uploaded_at')->whereDoesntHave('voters');
        }

        return view('voters.index', [
            'voters' => $voters->orderBy('serial')->orderBy('id')->paginate(24)->withQueryString(),
            'unitStatuses' => $units->with('ward.lga')->withCount('voters')->orderBy('name')->orderBy('id')->paginate(6, ['*'], 'unit_page')->withQueryString(),
            'totalUnits' => $totalUnits,
            'uploadedUnits' => $uploadedUnits,
            'pendingUnits' => $totalUnits - $uploadedUnits,
            'lgas' => Lga::orderBy('name')->get(),
            'wards' => Ward::where('lga_id', $filters['lga'] ?? 0)->orderBy('name')->get(),
            'pollingUnits' => PollingUnit::where('ward_id', $filters['ward'] ?? 0)->orderBy('name')->get(),
        ]);
    }
}
