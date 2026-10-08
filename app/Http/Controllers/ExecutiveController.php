<?php

namespace App\Http\Controllers;

use App\Models\ExecutiveAssignment;
use App\Models\ExecutivePosition;
use App\Models\Lga;
use App\Models\Membership;
use App\Models\PollingUnit;
use App\Models\Ward;
use App\Support\ExecutiveAppointments;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExecutiveController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'level' => ['nullable', Rule::in(array_keys(ExecutivePosition::LEVELS))],
            'lga' => ['nullable', 'integer', 'exists:lgas,id'],
            'ward' => ['nullable', 'integer', 'exists:wards,id'],
            'pu' => ['nullable', 'integer', 'exists:polling_units,id'],
            'gender' => ['nullable', 'in:male,female'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $query = ExecutiveAssignment::query();
        if ($request->filled('level')) {
            $query->whereHas('position', fn (Builder $query) => $query->where('level', $filters['level']));
        }
        $query->whereHas('membership', function (Builder $query) use ($filters): void {
            foreach (['lga', 'ward', 'pu', 'gender'] as $field) {
                if (! empty($filters[$field])) {
                    $query->where($field, $filters[$field]);
                }
            }
            if (! empty($filters['q'])) {
                $query->where(fn (Builder $query) => $query->where('name', 'like', '%'.$filters['q'].'%')->orWhere('cbm_id', $filters['q']));
            }
        });

        return view('dashboard.executives.index', [
            'assignments' => (clone $query)->with(['lga', 'membership.lgaInfo', 'membership.wardInfo', 'membership.puInfo', 'position.lga', 'position.ward', 'position.pollingUnit'])->orderByDesc('id')->paginate(25)->withQueryString(),
            'stats' => ['assignments' => (clone $query)->count(), 'members' => (clone $query)->distinct()->count('membership_id'), 'state' => (clone $query)->where('assignment_group', 'state')->count(), 'local' => (clone $query)->where('assignment_group', 'local')->count()],
            'lgas' => Lga::orderBy('name')->get(),
            'wards' => Ward::where('lga_id', $filters['lga'] ?? 0)->orderBy('name')->get(),
            'pollingUnits' => PollingUnit::where('ward_id', $filters['ward'] ?? 0)->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $data = $request->validate(['cbm_id' => ['nullable', 'string', 'max:255']]);
        $cbmId = trim($data['cbm_id'] ?? '');
        $members = $cbmId === '' ? collect() : Membership::with(['lgaInfo', 'wardInfo', 'puInfo', 'executiveAssignments.position'])->where('cbm_id', $cbmId)->limit(2)->get();
        $member = $members->count() === 1 ? $members->first() : null;

        return view('dashboard.executives.create', [
            'cbmId' => $cbmId, 'member' => $member, 'ambiguous' => $members->count() > 1,
            'positions' => $member ? ExecutiveAppointments::positionsFor($member) : collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'membership_id' => ['required', 'integer', 'exists:memberships,id'],
            'executive_position_id' => ['required', 'integer', 'exists:executive_positions,id'],
        ]);
        ExecutiveAppointments::assign((int) $data['membership_id'], (int) $data['executive_position_id']);

        return redirect()->route('executives.index')->with('status', 'Executive assigned successfully.');
    }

    public function destroy(ExecutiveAssignment $executive): RedirectResponse
    {
        $executive->delete();

        return redirect()->route('executives.index')->with('status', 'Executive assignment removed. The position is now vacant and membership is unchanged.');
    }
}
