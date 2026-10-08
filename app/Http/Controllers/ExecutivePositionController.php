<?php

namespace App\Http\Controllers;

use App\Models\ExecutivePosition;
use App\Models\Lga;
use App\Models\PollingUnit;
use App\Models\Ward;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExecutivePositionController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'level' => ['nullable', Rule::in(array_keys(ExecutivePosition::LEVELS))],
            'lga' => ['nullable', 'integer', 'exists:lgas,id'],
            'ward' => ['nullable', 'integer', 'exists:wards,id'],
            'pu' => ['nullable', 'integer', 'exists:polling_units,id'],
        ]);
        $level = $filters['level'] ?? 'state';
        $query = ExecutivePosition::with(['lga', 'ward', 'pollingUnit'])->withCount('assignments')->where('level', $level);
        foreach (['lga' => 'lga_id', 'ward' => 'ward_id', 'pu' => 'polling_unit_id'] as $input => $column) {
            if (! in_array($level, ['state', 'lga']) && $request->filled($input)) {
                $query->where($column, $filters[$input]);
            }
        }

        return view('dashboard.executives.positions', [
            'positions' => $query->orderBy('name')->orderBy('id')->paginate(25)->withQueryString(),
            'level' => $level,
            'lgas' => Lga::orderBy('name')->get(),
            'wards' => Ward::where('lga_id', $filters['lga'] ?? 0)->orderBy('name')->get(),
            'pollingUnits' => PollingUnit::where('ward_id', $filters['ward'] ?? 0)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'level' => ['required', Rule::in(array_keys(ExecutivePosition::LEVELS))],
            'lga' => ['exclude_if:level,state,lga', 'required', 'integer', 'exists:lgas,id'],
            'ward' => ['exclude_unless:level,ward,polling_unit', 'required', 'integer', Rule::exists('wards', 'id')->where('lga_id', $request->input('lga'))],
            'pu' => ['exclude_unless:level,polling_unit', 'required', 'integer', Rule::exists('polling_units', 'id')->where('ward_id', $request->input('ward'))],
        ]);
        $name = preg_replace('/\s+/u', ' ', trim($data['name']));
        try {
            ExecutivePosition::create([
                'name' => $name, 'name_key' => mb_strtolower($name), 'level' => $data['level'],
                'location_key' => match ($data['level']) {
                    'state', 'lga' => 0, 'ward' => $data['ward'], 'polling_unit' => $data['pu']
                },
                'lga_id' => $data['lga'] ?? null, 'ward_id' => $data['ward'] ?? null, 'polling_unit_id' => $data['pu'] ?? null,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['name' => 'This position already exists at this location.']);
        }

        return redirect()->route('executive-positions.index', array_intersect_key($data, array_flip(['level', 'lga', 'ward', 'pu'])))->with('status', 'Executive position added.');
    }

    public function update(Request $request, ExecutivePosition $executivePosition): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);
        $name = preg_replace('/\s+/u', ' ', trim($data['name']));
        try {
            $executivePosition->update(['name' => $name, 'name_key' => mb_strtolower($name)]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['name' => 'This position already exists at this location.']);
        }

        return back()->with('status', 'Position name updated.');
    }

    public function destroy(ExecutivePosition $executivePosition): RedirectResponse
    {
        DB::transaction(function () use ($executivePosition): void {
            $position = ExecutivePosition::whereKey($executivePosition->id)->lockForUpdate()->firstOrFail();
            if ($position->assignments()->exists()) {
                throw ValidationException::withMessages(['position' => 'Remove the executive assignment before deleting this position.']);
            }
            $position->delete();
        });

        return back()->with('status', 'Vacant position deleted.');
    }
}
