<?php

namespace App\Http\Controllers;

use App\Models\Exco;
use App\Models\Lga;
use App\Models\Membership;
use App\Models\PollingUnit;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExcoController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'lga' => ['nullable', 'integer', 'exists:lgas,id'],
            'ward' => ['nullable', 'integer', 'exists:wards,id'],
            'pu' => ['nullable', 'integer', 'exists:polling_units,id'],
            'gender' => ['nullable', 'in:male,female'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $query = Membership::query()->whereHas('exco');
        foreach (['lga', 'ward', 'pu', 'gender'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $filters[$field]);
            }
        }
        if ($request->filled('q')) {
            $query->where(function (Builder $query) use ($filters): void {
                $query->where('name', 'like', '%'.$filters['q'].'%')
                    ->orWhere('cbm_id', $filters['q'])
                    ->orWhere('cbm_delimitation_code', $filters['q']);
            });
        }

        return view('dashboard.excos.index', [
            'members' => (clone $query)->with(['lgaInfo', 'wardInfo', 'puInfo'])->orderBy('name')->orderBy('id')->paginate(25)->withQueryString(),
            'stats' => [
                'total' => (clone $query)->count(),
                'male' => (clone $query)->where('gender', 'male')->count(),
                'female' => (clone $query)->where('gender', 'female')->count(),
            ],
            'lgas' => Lga::orderBy('name')->get(),
            'wards' => $request->filled('lga') ? Ward::where('lga_id', $filters['lga'])->orderBy('name')->get() : collect(),
            'pollingUnits' => $request->filled('ward') ? PollingUnit::where('ward_id', $filters['ward'])->orderBy('name')->get() : collect(),
            'filters' => array_filter($filters, fn ($value) => $value !== null && $value !== ''),
        ]);
    }

    public function create(Request $request): View
    {
        $data = $request->validate(['member_id' => ['nullable', 'string', 'max:255']]);
        $memberId = trim($data['member_id'] ?? '');
        $matches = $memberId === '' ? collect() : Membership::with(['exco', 'lgaInfo', 'wardInfo', 'puInfo'])
            ->where(function (Builder $query) use ($memberId): void {
                $query->where('cbm_id', $memberId)->orWhere('cbm_delimitation_code', $memberId);
            })->limit(2)->get();

        return view('dashboard.excos.create', ['memberId' => $memberId, 'matches' => $matches]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['membership_id' => ['required', 'integer', 'exists:memberships,id']]);
        $exco = Exco::firstOrCreate(['membership_id' => $data['membership_id']]);

        return redirect()->route('excos.index')->with('status', $exco->wasRecentlyCreated
            ? 'Member added to Excos successfully.' : 'This member is already an Exco.');
    }

    public function destroy(Exco $exco): RedirectResponse
    {
        $exco->delete();

        return redirect()->route('excos.index')->with('status', 'Removed from Excos. The membership record has been kept.');
    }
}
