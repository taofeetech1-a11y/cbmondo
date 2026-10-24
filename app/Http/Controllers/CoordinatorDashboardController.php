<?php

namespace App\Http\Controllers;

use App\Models\CoordinatorAgent;
use App\Models\Membership;
use App\Models\PollingUnit;
use App\Models\Voter;
use App\Support\MembershipDates;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CoordinatorDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $unit = $this->unit($request);
        $voters = Voter::where('polling_unit_id', $unit->id);
        $total = (clone $voters)->count();
        $pending = (clone $voters)->where(fn (Builder $query) => $query->whereNull('phone')->orWhere('phone', ''))->count();
        $stats = ['total' => $total, 'collected' => $total - $pending, 'pending' => $pending,
            'consented' => (clone $voters)->where('consent_given', true)->count(),
            'not_consented' => (clone $voters)->where('consent_given', false)->count(),
            'consent_unknown' => (clone $voters)->whereNull('consent_given')->count(),
            'spoken_to' => (clone $voters)->where('spoken_to', true)->count(),
            'apc_members' => (clone $voters)->where('apc_member', true)->count()];

        $wardMembers = Membership::inWard($unit->ward);
        $wardMemberTotal = (clone $wardMembers)->count();
        $membershipTrend = MembershipDates::dailyTrend($wardMembers, CarbonImmutable::today()->subDays(29), CarbonImmutable::tomorrow());

        return view('coordinator.dashboard', compact('unit', 'stats', 'wardMemberTotal', 'membershipTrend'));
    }

    public function register(Request $request): View
    {
        $unit = $this->unit($request);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'collection' => ['nullable', Rule::in(['pending', 'collected'])], 'consent' => ['nullable', Rule::in(['given', 'declined', 'unknown'])]]);
        $query = Voter::where('polling_unit_id', $unit->id)->with('pollingUnit');
        $total = (clone $query)->count();
        if (filled(trim($filters['q'] ?? ''))) {
            $search = '%'.trim($filters['q']).'%';
            $query->where(fn (Builder $query) => $query->where('full_name', 'like', $search)->orWhere('vin', 'like', $search));
        }
        if (($filters['collection'] ?? null) === 'pending') {
            $query->where(fn (Builder $query) => $query->whereNull('phone')->orWhere('phone', ''));
        } elseif (($filters['collection'] ?? null) === 'collected') {
            $query->whereNotNull('phone')->where('phone', '!=', '');
        }
        if (($filters['consent'] ?? null) === 'given') {
            $query->where('consent_given', true);
        } elseif (($filters['consent'] ?? null) === 'declined') {
            $query->where('consent_given', false);
        } elseif (($filters['consent'] ?? null) === 'unknown') {
            $query->whereNull('consent_given');
        }
        $voters = $query->orderBy('serial')->orderBy('id')->paginate(24)->withQueryString();

        return view('coordinator.register', compact('unit', 'total', 'voters'));
    }

    public function contributions(Request $request): View
    {
        $unit = $this->unit($request);
        $unit->loadCount('voters');
        $unitLeaderboard = CoordinatorAgent::with('membership:id,name')
            ->whereHas('membership', fn (Builder $query) => $query->where('pu', $unit->id))
            ->withCount(['phoneCollections as points' => fn (Builder $query) => $query->where('polling_unit_id', $unit->id)])
            ->orderByDesc('points')->orderBy('id')->get();
        $coordinatorPoints = (int) $unitLeaderboard->firstWhere('id', $request->user()->id)?->points;
        $coordinatorRank = 1 + $unitLeaderboard->where('points', '>', $coordinatorPoints)->count();

        return view('coordinator.contributions', compact('unit', 'unitLeaderboard', 'coordinatorPoints', 'coordinatorRank'));
    }

    public function memberships(Request $request): View
    {
        $unit = $this->unit($request);
        $ward = $unit->ward;
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'polling_unit_id' => ['nullable', 'integer', Rule::exists('polling_units', 'id')->where('ward_id', $ward->id)],
        ]);
        $query = Membership::inWard($ward);
        if (filled(trim($filters['q'] ?? ''))) {
            $search = '%'.trim($filters['q']).'%';
            $query->where(fn (Builder $query) => $query->where('name', 'like', $search)->orWhere('cbm_id', 'like', $search)->orWhere('phone', 'like', $search));
        }
        if ($request->filled('polling_unit_id')) {
            $query->where('pu', (string) $filters['polling_unit_id']);
        }
        $membershipTrend = MembershipDates::dailyTrend($query, CarbonImmutable::today()->subDays(29), CarbonImmutable::tomorrow());
        $members = $query->select(['id', 'name', 'cbm_id', 'phone', 'pu', 'created_at'])
            ->with(['puInfo' => fn (BelongsTo $units): BelongsTo => $units->where('ward_id', $ward->id)->select(['id', 'name', 'delimitation_code'])])
            ->latest('created_at')->orderByDesc('id')->paginate(20)->withQueryString();

        return view('coordinator.memberships', [
            'ward' => $ward, 'members' => $members, 'membershipTrend' => $membershipTrend,
            'pollingUnits' => $ward->pollingUnits()->orderBy('name')->get(['id', 'name', 'delimitation_code']),
        ]);
    }

    private function unit(Request $request): PollingUnit
    {
        $unit = $request->user('coordinator')->membership?->puInfo;
        abort_unless($unit, 403);
        $unit->loadMissing('ward.lga');

        return $unit;
    }
}
