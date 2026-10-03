<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateMembershipRequest;
use App\Models\Lga;
use App\Models\Membership;
use App\Models\PollingUnit;
use App\Models\Ward;
use App\Support\MembershipCardBatch;
use App\Support\MembershipDates;
use App\Support\MembershipExport;
use App\Support\MembershipTable;
use App\View\MembershipCard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MembershipController extends Controller
{
    public function bulkCards(Request $request, MembershipCardBatch $batch): StreamedResponse
    {
        $data = $request->validate([
            'format' => ['required', 'in:zip,pdf'],
            'scope' => ['required', 'in:selected,filtered'],
            'ids' => ['exclude_unless:scope,selected', 'required', 'array', 'min:1', 'max:500'],
            'ids.*' => ['integer', 'distinct'],
        ]);
        $query = MembershipTable::ordered($this->filteredMembers($request), $request);
        if ($data['scope'] === 'selected') {
            $query->whereIn('id', $data['ids']);
        }
        $count = (clone $query)->count();
        if ($data['scope'] === 'selected' && $count !== count($data['ids'])) {
            throw ValidationException::withMessages(['ids' => 'Some selected members no longer match these filters. Clear your selection and try again.']);
        }
        if ($count === 0) {
            throw ValidationException::withMessages(['scope' => 'No members match this card download. Select members or change your filters.']);
        }
        if ($data['format'] === 'pdf' && $count > 200) {
            throw ValidationException::withMessages(['format' => 'PDF downloads support up to 200 cards. Narrow your filters or select fewer members; ZIP supports the full selection.']);
        }

        return $batch->download($query, $data['format']);
    }

    public function card(Request $request, Membership $membership, MembershipCard $card): Response
    {
        $filename = 'CBM-ID-'.$membership->id.'.png';

        return response($card->render($membership), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => ($request->boolean('download') ? 'attachment' : 'inline').'; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function show(Request $request, Membership $membership): View
    {
        $membership->load(['lgaInfo', 'wardInfo', 'puInfo']);

        return view('dashboard.members.show', ['member' => $membership, 'filters' => $this->dashboardFilters($request), 'directoryRoute' => $this->directoryRoute($request)]);
    }

    public function edit(Request $request, Membership $membership): View
    {
        return view('dashboard.members.edit', [
            'member' => $membership,
            'directoryRoute' => $this->directoryRoute($request),
            'filters' => $this->dashboardFilters($request),
            'lgas' => Lga::orderBy('name')->get(),
            'wards' => Ward::where('lga_id', old('lga', $membership->lga))->orderBy('name')->get(),
            'pollingUnits' => PollingUnit::where('ward_id', old('ward', $membership->ward))->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateMembershipRequest $request, Membership $membership): RedirectResponse
    {
        DB::transaction(function () use ($request, $membership): void {
            $member = Membership::whereKey($membership->id)->lockForUpdate()->firstOrFail();
            $data = $request->safe()->except('has_voters_card');

            if ($request->input('has_voters_card') === 'no') {
                $data = array_merge($data, ['ward' => null, 'pu' => null, 'same_address' => null, 'delimitation_code' => null, 'cbm_delimitation_code' => null]);
            } elseif ((string) $member->pu !== (string) $data['pu']) {
                $pollingUnit = PollingUnit::whereKey($data['pu'])->lockForUpdate()->firstOrFail();
                $data['delimitation_code'] = $pollingUnit->delimitation_code;
                $data['cbm_delimitation_code'] = $pollingUnit->delimitation_code.'/'.str_pad((string) $pollingUnit->next_member_number, 3, '0', STR_PAD_LEFT);
                $pollingUnit->increment('next_member_number');
            }

            $member->update($data);
        });

        return redirect()->route($this->directoryRoute($request), $this->dashboardFilters($request))->with('status', 'Member updated successfully.');
    }

    private function directoryRoute(Request $request): string
    {
        return $request->query('from') === 'excos' ? 'excos.index' : 'membership.index';
    }

    /** @return array<string, string> */
    private function dashboardFilters(Request $request): array
    {
        $filters = $request->query('filters', []);

        return is_array($filters)
            ? array_filter(array_intersect_key($filters, array_flip(['lga', 'ward', 'pu', 'has_voters_card', 'gender', 'q', 'period', 'date_from', 'date_to', 'trend', 'sort', 'direction', 'per_page', 'page'])), 'is_string')
            : [];
    }

    public function export(Request $request, string $type, MembershipExport $export): StreamedResponse
    {
        abort_unless(in_array($type, ['xlsx', 'csv', 'json', 'sql'], true), 404);

        return $export->download(MembershipTable::ordered($this->filteredMembers($request), $request), $type);
    }

    /** @return Builder<Membership> */
    private function filteredMembers(Request $request): Builder
    {
        $request->validate(['gender' => ['nullable', 'string', 'in:male,female']]);
        [$dateStart, $dateEnd] = MembershipDates::range($request);

        return Membership::query()
            ->when($request->filled('gender'), fn ($query) => $query->where('gender', $request->input('gender')))
            ->when($dateStart, fn ($query) => $query->where('created_at', '>=', $dateStart))
            ->when($dateEnd, fn ($query) => $query->where('created_at', '<', $dateEnd))
            ->when($request->filled('lga'), fn ($q) => $q->where('lga', $request->lga))
            ->when($request->filled('ward'), fn ($q) => $q->where('ward', $request->ward))
            ->when($request->filled('pu'), fn ($q) => $q->where('pu', $request->pu))
            ->when($request->filled('has_voters_card'), function ($q) use ($request) {
                $request->has_voters_card === 'yes'
                    ? $q->whereNotNull('ward')
                    : $q->whereNull('ward');
            })
            ->when($request->filled('q'), function ($q) use ($request) {
                $q->where(function ($q) use ($request) {
                    $q->where('name', 'like', "%{$request->q}%")
                        ->orWhere('phone', 'like', "%{$request->q}%")
                        ->orWhere('cbm_id', 'like', "%{$request->q}%");
                });
            });
    }

    public function index(Request $request): View
    {

        $lgas = Lga::orderBy('name')->get();

        $wards = $request->filled('lga') ? Ward::where('lga_id', $request->lga)->orderBy('name')->get() : collect();

        $pollingUnits = $request->filled('ward') ? PollingUnit::where('ward_id', $request->ward)->orderBy('pu_number')->get() : collect();

        $filteredMembers = $this->filteredMembers($request);

        $trendData = MembershipDates::trend($filteredMembers, $request);

        $members = MembershipTable::ordered(clone $filteredMembers, $request)
            ->with(['lgaInfo', 'wardInfo', 'puInfo'])
            ->paginate($request->integer('per_page') ?: 10)
            ->withQueryString();

        $total = $members->total();
        $votersCard = (clone $filteredMembers)->whereNull('ward')->count();

        $percentOf = fn (int $part) => $total ? round($part / $total * 100) : 0;

        $stats = [
            'total' => $total,
            'votersCard' => $votersCard,
            'vCP' => $percentOf($votersCard),
        ];

        $breakdownBase = clone $filteredMembers;

        if ($request->filled('ward')) {
            $breakdownLabel = 'Polling Unit';
            $names = PollingUnit::where('ward_id', $request->ward)->pluck('delimitation_code', 'id');
            $rows = (clone $breakdownBase)->where('ward', $request->ward)
                ->selectRaw('pu as key_id, count(*) as count')
                ->groupBy('pu')
                ->orderByDesc('count')
                ->get();
        } elseif ($request->filled('lga')) {
            $breakdownLabel = 'Ward';
            $names = Ward::where('lga_id', $request->lga)->pluck('name', 'id');
            $rows = (clone $breakdownBase)->where('lga', $request->lga)
                ->selectRaw('ward as key_id, count(*) as count')
                ->groupBy('ward')
                ->orderByDesc('count')
                ->get();
        } else {
            $breakdownLabel = 'LGA';
            $names = Lga::pluck('name', 'id');
            $rows = (clone $breakdownBase)
                ->selectRaw('lga as key_id, count(*) as count')
                ->groupBy('lga')
                ->orderByDesc('count')
                ->get();
        }

        $maxCount = $rows->max('count') ?: 1;
        $breakdown = $rows->map(fn ($row) => [
            'name' => $names[$row->key_id] ?? 'Unknown',
            'count' => $row->count,
            'percent' => round($row->count / $maxCount * 100),
        ]);

        [$dateStart, $dateEnd] = MembershipDates::range($request);
        $dateScope = $dateStart ? $dateStart->format('d M Y').' – '.$dateEnd->subDay()->format('d M Y') : 'All dates';

        $chartScope = array_values(array_filter([
            $dateScope.' ('.config('app.timezone').')',
            $request->filled('lga') ? 'LGA: '.($lgas->firstWhere('id', $request->lga)?->name ?? $request->lga) : null,
            $request->filled('ward') ? 'Ward: '.($wards->firstWhere('id', $request->ward)?->name ?? $request->ward) : null,
            $request->filled('pu') ? 'Polling unit: '.($pollingUnits->firstWhere('id', $request->pu)?->name ?? $request->pu) : null,
            $request->filled('has_voters_card') ? ($request->has_voters_card === 'yes' ? 'With voter cards' : 'Without voter cards') : null,
            $request->filled('gender') ? 'Gender: '.ucfirst($request->input('gender')) : null,
            $request->filled('q') ? 'Search: '.$request->q : null,
        ]));

        $filterChips = [];
        $chipLabels = [
            'lga' => 'LGA: '.($lgas->firstWhere('id', $request->lga)?->name ?? $request->lga),
            'ward' => 'Ward: '.($wards->firstWhere('id', $request->ward)?->name ?? $request->ward),
            'pu' => 'Polling unit: '.($pollingUnits->firstWhere('id', $request->pu)?->name ?? $request->pu),
            'has_voters_card' => $request->has_voters_card === 'yes' ? 'With voter cards' : 'Without voter cards',
            'gender' => 'Gender: '.ucfirst($request->input('gender') ?? ''),
            'q' => 'Search: '.$request->q,
            'period' => 'Dates: '.$dateScope,
        ];
        foreach ($chipLabels as $key => $label) {
            if (! $request->filled($key) || ($key === 'period' && $request->period === 'all')) {
                continue;
            }
            $remove = match ($key) {
                'lga' => ['lga', 'ward', 'pu', 'page'],
                'ward' => ['ward', 'pu', 'page'],
                'period' => ['period', 'date_from', 'date_to', 'page'],
                default => [$key, 'page'],
            };
            $filterChips[] = ['label' => $label, 'url' => route('membership.index', $request->except($remove))];
        }

        return view('dashboard.index', compact(
            'lgas',
            'wards',
            'pollingUnits',
            'members',
            'stats',
            'breakdownLabel',
            'breakdown',
            'chartScope',
            'trendData',
            'dateScope',
            'filterChips'
        ));
    }
}
