@extends('voters.layout')
@section('title', isset($assignedUnit) ? $assignedUnit->name.' — Unit workspace' : 'Voter directory — CBM Ondo')
@section('voter-content')
@if (isset($assignedUnit))
<section class="unit-room" aria-label="Your polling unit workspace">
    <div class="unit-room-banner" aria-hidden="true"><span>PU</span><span class="unit-room-pattern">#</span></div>
    <div class="unit-room-body">
        <span class="unit-room-label">YOUR POLLING UNIT · YOUR WORKSPACE</span>
        <h1>{{ $assignedUnit->name }}</h1>
        <p class="unit-room-location">{{ $assignedUnit->ward->lga->name }} <span aria-hidden="true">/</span> {{ $assignedUnit->ward->name }}</p>
        <div class="unit-room-meta"><span class="unit-room-code"># {{ $assignedUnit->delimitation_code }}</span><span>Polling-unit workspace</span></div>
        <div class="unit-room-welcome"><span class="unit-room-avatar" aria-hidden="true">{{ mb_substr($coordinatorName, 0, 1) }}</span><div><strong>Welcome, {{ $coordinatorName }}</strong><p>This is your unit’s space. Find your voters and keep their phone numbers up to date.</p></div></div>
    </div>
</section>
@else
<div class="page-head voter-page-head">
    <div><span class="eyebrow">POLLING-UNIT REGISTERS</span><h1>Voter directory</h1><p>Your imported voter records, together in one place.</p></div>
    @can('import-voters')<a class="btn btn-primary" href="{{ route('voters.import') }}">＋ Import a register</a>@endcan
</div>
@endif
@if ($errors->any())
    <div class="member-notice member-error" role="alert">{{ $errors->first() }}</div>
@endif
@unless (isset($assignedUnit))
<form method="GET" action="{{ route('voters.index') }}" class="panel voter-filters">
    <div class="field"><label for="filter-lga">LGA</label><select id="filter-lga" name="lga" onchange="this.form.elements.ward.value='';this.form.elements.polling_unit_id.value='';this.form.submit()"><option value="">All LGAs</option>@foreach ($lgas as $lga)<option value="{{ $lga->id }}" @selected(request('lga') == $lga->id)>{{ $lga->name }}</option>@endforeach</select></div>
    <div class="field"><label for="filter-ward">Ward</label><select id="filter-ward" name="ward" @disabled($wards->isEmpty()) onchange="this.form.elements.polling_unit_id.value='';this.form.submit()"><option value="">All wards</option>@foreach ($wards as $ward)<option value="{{ $ward->id }}" @selected(request('ward') == $ward->id)>{{ $ward->code }} — {{ $ward->name }}</option>@endforeach</select></div>
    <div class="field"><label for="filter-unit">Polling unit</label><select id="filter-unit" name="polling_unit_id" @disabled($pollingUnits->isEmpty())><option value="">All polling units</option>@foreach ($pollingUnits as $unit)<option value="{{ $unit->id }}" @selected(request('polling_unit_id') == $unit->id)>{{ $unit->delimitation_code }} — {{ $unit->name }}</option>@endforeach</select></div>
    <div class="field"><label for="filter-search">Search voters</label><input id="filter-search" name="q" type="search" value="{{ request('q') }}" placeholder="Name or VIN" maxlength="100"></div>
    <div class="field"><label for="filter-status">Polling-unit upload status</label><select id="filter-status" name="upload_status"><option value="">All statuses</option><option value="uploaded" @selected(request('upload_status') === 'uploaded')>Uploaded</option><option value="pending" @selected(request('upload_status') === 'pending')>Not uploaded</option></select></div>
    <div class="voter-filter-actions"><button class="btn btn-primary" type="submit">Apply filters</button><a class="btn btn-outline" href="{{ route(auth()->user() instanceof \App\Models\CoordinatorAgent ? 'coordinator.dashboard' : 'voters.index') }}">Reset</a></div>
</form>
@endunless
@if (isset($assignedUnit))
<section class="panel unit-phone-progress" aria-label="Phone number collection progress">
    <div class="voter-results-heading"><h2>Your unit’s progress</h2><strong>{{ $phoneProgress['percent'] }}% complete</strong></div>
    <progress class="unit-phone-progress-bar" max="100" value="{{ $phoneProgress['percent'] }}" aria-label="Phone number collection completion">{{ $phoneProgress['percent'] }}%</progress>
    <div class="unit-phone-progress-counts">
        <div><span>PHONE NUMBERS ADDED</span><strong>{{ number_format($phoneProgress['added']) }}</strong><small>Voters completed</small></div>
        <div><span>STILL TO ADD</span><strong>{{ number_format($phoneProgress['missing']) }}</strong><small>Voters awaiting a phone number</small></div>
    </div>
</section>
<section class="panel unit-contributions" aria-label="Coordinator contributions">
    <div class="voter-results-heading"><h2>Your contribution</h2><span>Rank #{{ $coordinatorRank }} in your unit</span></div>
    <div class="unit-points"><strong>{{ number_format($coordinatorPoints) }}</strong><span>points earned</span></div>
    <p class="voter-help">1 completed voter = 1 point. Each voter earns credit once. Equal scores share the same rank. Previously saved numbers without attribution earn no points.</p>
    <h3>Unit leaderboard</h3>
    <ol class="unit-leaderboard">
        @foreach($unitLeaderboard as $participant)
            <li @if($participant->id === auth()->id()) class="is-you" @endif><span class="unit-leaderboard-rank">#{{ 1 + $unitLeaderboard->where('points', '>', $participant->points)->count() }}</span><span class="unit-leaderboard-name">{{ $participant->membership->name }} @if($participant->id === auth()->id())<small>(you)</small>@endif</span><strong>{{ number_format($participant->points) }} <small>pts</small></strong></li>
        @endforeach
    </ol>
</section>
@else
<section class="voter-overview" aria-label="Register overview">
    <div><span class="eyebrow">{{ isset($assignedUnit) ? 'VOTERS AWAITING PHONE NUMBERS' : 'MATCHING VOTERS' }}</span><strong>{{ number_format($voters->total()) }}</strong><span>{{ isset($assignedUnit) ? 'Voters in your unit who still need a phone number' : 'For the current location and search' }}</span></div>
    @unless (isset($assignedUnit))
    <div><span class="eyebrow">REGISTERS UPLOADED</span><strong>{{ number_format($uploadedUnits) }}</strong><span>Of {{ number_format($totalUnits) }} polling units in this location</span></div>
    <div class="voter-pending-summary"><span class="eyebrow">NOT YET UPLOADED</span><strong>{{ number_format($pendingUnits) }}</strong><span>Polling units awaiting a register</span></div>
    @endunless
</section>
@endif
@unless (isset($assignedUnit))
<div class="voter-results-heading"><h2>Polling-unit upload status</h2><span>{{ number_format($unitStatuses->total()) }} polling units</span></div>
<p class="voter-status-help">Upload status applies to the polling-unit cards below. Name and VIN searches apply to the voter list. Older voters without a polling-unit link need to be reimported to appear under a location.</p>
<div class="voter-grid voter-status-grid">
    @forelse ($unitStatuses as $unit)
        @php($uploaded = $unit->register_uploaded_at !== null || $unit->voters_count > 0)
        <article class="voter-card voter-unit-card">
            <span class="voter-status-badge {{ $uploaded ? 'is-uploaded' : 'is-pending' }}">{{ $uploaded ? '✓ Uploaded' : 'Not uploaded' }}</span>
            <h3>{{ $unit->name }}</h3><span class="voter-unit-code">{{ $unit->delimitation_code }}</span>
            <p>{{ $unit->ward->lga->name }} · {{ $unit->ward->name }}</p>
            <div class="voter-unit-footer"><strong>{{ number_format($unit->voters_count) }} voters</strong>
                @if ($uploaded)
                    <a href="{{ route(auth()->user() instanceof \App\Models\CoordinatorAgent ? 'coordinator.dashboard' : 'voters.index', ['lga' => $unit->ward->lga_id, 'ward' => $unit->ward_id, 'polling_unit_id' => $unit->id]) }}">View voters →</a>
                @else
                    <span>Awaiting PDF register</span>
                @endif
            </div>
        </article>
    @empty
        <div class="panel voter-empty"><h2>No polling units match</h2><p>Try another location or upload status.</p></div>
    @endforelse
</div>
@if ($unitStatuses->hasPages())
    <div class="voter-pagination">{{ $unitStatuses->links('pagination::simple-bootstrap-4') }}</div>
@endif
@endunless
<div class="voter-results-heading"><h2>{{ isset($assignedUnit) ? 'Voters awaiting phone numbers' : 'Registered voters' }}</h2><span>{{ $voters->total() ? number_format($voters->firstItem()).'–'.number_format($voters->lastItem()).' of '.number_format($voters->total()) : (isset($assignedUnit) ? 'No records remaining' : 'No records yet') }}</span></div>
<div class="voter-grid">
    @forelse ($voters as $voter)
        <article class="voter-card">
            <div class="voter-card-heading">
                @if ($voter->photo_path)
                    <img class="voter-portrait" src="{{ route('voters.photo', $voter) }}" alt="Photo of {{ $voter->full_name }}" loading="lazy" width="64" height="80">
                @else
                    <div class="voter-portrait voter-initial" aria-label="No photo available">{{ mb_strtoupper(mb_substr($voter->full_name, 0, 1)) }}</div>
                @endif
                <div class="voter-identity"><span class="voter-serial">{{ $voter->pollingUnit?->delimitation_code ?: 'Delimitation code not recorded' }}</span><h3>{{ $voter->full_name }}</h3>@unless(isset($assignedUnit))<span class="voter-demographic">{{ $voter->gender }} · {{ $voter->age }} years</span>@endunless</div>
            </div>
            <dl class="voter-details">
                @unless(isset($assignedUnit))
                <div class="voter-vin"><dt>Voter identification number</dt><dd>{{ $voter->vin }}</dd></div>
                <div><dt>Occupation</dt><dd>{{ $voter->occupation ?: 'Not recorded' }}</dd></div>
                <div><dt>Phone number</dt><dd>{{ $voter->phone ?: 'Not recorded' }}</dd></div>
                @if($voter->phone_added_at)
                    <div><dt>Phone number added by</dt><dd>{{ $voter->phoneAddedBy?->membership?->name ?? 'Former coordinator' }} · {{ $voter->phone_added_at->format('d M Y, H:i') }}</dd></div>
                @endif
                @endunless
                <div class="voter-location"><dt>Polling unit · PDF register</dt><dd>{{ $voter->polling_unit ?: 'Not recorded' }}</dd></div>
            </dl>
            <div class="voter-card-actions"><a class="btn btn-outline" href="{{ route('voters.edit', $voter) }}" aria-label="{{ isset($assignedUnit) ? 'Add phone number for' : 'Edit' }} {{ $voter->full_name }}">{{ isset($assignedUnit) ? 'Add phone number →' : 'Edit voter →' }}</a></div>
        </article>
    @empty
        <section class="panel voter-empty"><span class="voter-symbol" aria-hidden="true">▤</span><h2>{{ isset($assignedUnit) ? 'No voters awaiting phone numbers' : 'No voters found' }}</h2><p>{{ isset($assignedUnit) ? 'There are no voters awaiting a phone number in this list.' : 'No voter records match your current location and search.' }}</p>@can('import-voters')<a class="btn btn-primary" href="{{ route('voters.import') }}">Import a register</a>@endcan</section>
    @endforelse
</div>
@if ($voters->hasPages())
    <div class="voter-pagination">{{ $voters->links('pagination::simple-bootstrap-4') }}</div>
@endif
@endsection
