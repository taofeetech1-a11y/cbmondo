@extends('coordinator.canvass-layout')
@section('title', 'Ward Memberships — CBM Ondo')
@section('screen-title', 'Ward Memberships')
@section('back-url', route('coordinator.dashboard'))
@section('content')
<section class="canvass-location" aria-label="Your membership ward"><div class="canvass-location-top"><x-canvass-icon name="pin"/><strong>Ward {{ $ward->code }} · {{ $ward->name }}</strong></div><p>{{ $ward->lga->name }} · Memberships across your ward, read-only</p></section>
@if($errors->any())<div class="canvass-notice canvass-error" role="alert">{{ $errors->first() }}</div>@endif
<form method="GET" action="{{ route('coordinator.memberships') }}" class="canvass-search-form">
    <label class="canvass-search"><x-canvass-icon name="search"/><span class="canvass-sr-only">Search members by name, membership ID or phone</span><input name="q" type="search" placeholder="Name, membership ID or phone…" value="{{ request('q') }}" maxlength="100"><button type="submit">Search</button></label>
    <div class="canvass-filters"><label><span>Polling unit in your ward</span><select name="polling_unit_id"><option value="">All ward polling units</option>@foreach($pollingUnits as $unit)<option value="{{ $unit->id }}" @selected(request('polling_unit_id') == $unit->id)>{{ $unit->delimitation_code }} · {{ $unit->name }}</option>@endforeach</select></label><button type="submit">Apply</button><a href="{{ route('coordinator.memberships') }}">Reset</a></div>
</form>
<section class="canvass-ward-overview">
    <div class="canvass-member-stats"><div><span>Matching members · all dates</span><strong>{{ number_format($members->total()) }}</strong></div><div><span>Registered · last 30 days</span><strong>{{ number_format(array_sum($membershipTrend['counts'])) }}</strong></div></div>
    <x-registration-trend :trend="$membershipTrend"/>
    <p class="canvass-footnote">The list and chart follow your search and polling-unit filter. The list includes all registration dates; the chart shows the last 30 days. Members without a ward assignment are excluded.</p>
</section>
<div class="canvass-list-heading"><h2>Registered members</h2><span>{{ $members->firstItem() ?? 0 }}–{{ $members->lastItem() ?? 0 }} of {{ number_format($members->total()) }}</span></div>
<section class="canvass-members" aria-label="Members registered in your ward">
@forelse($members as $member)
    <article class="canvass-member-card"><div class="canvass-member-heading"><span class="canvass-member-avatar" aria-hidden="true">{{ mb_substr($member->name ?: '?', 0, 1) }}</span><div><h3>{{ $member->name ?: 'Name not recorded' }}</h3><p>{{ $member->cbm_id }}</p></div></div><dl>
        <div><dt>Phone number</dt><dd>{{ $member->phone ?: 'Not recorded' }}</dd></div>
        <div><dt>Polling unit</dt><dd>{{ $member->puInfo?->name ?? 'Not assigned in this ward' }}@if($member->puInfo)<small>{{ $member->puInfo->delimitation_code }}</small>@endif</dd></div>
        <div><dt>Registered</dt><dd>@if($member->created_at)<time datetime="{{ $member->created_at->toIso8601String() }}">{{ $member->created_at->format('d M Y, H:i') }}</time>@else Not recorded @endif</dd></div>
    </dl></article>
@empty
    <div class="canvass-empty"><x-canvass-icon name="users"/><h2>No members found</h2><p>No ward members match your current search and polling unit.</p><a href="{{ route('coordinator.memberships') }}">Reset filters</a></div>
@endforelse
</section>
<div class="voter-pagination">{{ $members->links('pagination::simple-bootstrap-4') }}</div>
@endsection
