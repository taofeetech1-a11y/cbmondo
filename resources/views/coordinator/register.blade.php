@extends('coordinator.canvass-layout')
@section('title', 'Polling Unit Register — CBM Ondo')
@section('screen-title', 'Polling Unit Register')
@section('back-url', route('coordinator.dashboard'))
@section('content')
@include('coordinator.unit-strip')
<form class="canvass-search-form" method="GET" action="{{ route('coordinator.register') }}">
    <label class="canvass-search"><x-canvass-icon name="search"/><span class="canvass-sr-only">Search name or VIN</span><input name="q" type="search" value="{{ request('q') }}" placeholder="Search name or VIN…" maxlength="100"><button type="submit">Search</button></label>
    <div class="canvass-filters"><label><span>Phone numbers</span><select name="collection"><option value="">All voters</option><option value="pending" @selected(request('collection') === 'pending')>Not collected</option><option value="collected" @selected(request('collection') === 'collected')>Collected</option></select></label><label><span>Contact consent</span><select name="consent"><option value="">All responses</option><option value="given" @selected(request('consent') === 'given')>Consented</option><option value="declined" @selected(request('consent') === 'declined')>Not consented</option><option value="unknown" @selected(request('consent') === 'unknown')>Not recorded</option></select></label><button type="submit">Apply</button><a href="{{ route('coordinator.register') }}">Reset</a></div>
</form>
@if($errors->any())<div class="canvass-notice canvass-error" role="alert">{{ $errors->first() }}</div>@endif
<div class="canvass-list-heading"><h2>Unit voters</h2><span>{{ $voters->firstItem() ?? 0 }}–{{ $voters->lastItem() ?? 0 }} of {{ number_format($voters->total()) }}</span></div>
<section class="canvass-register" aria-label="Polling unit voter list">
@forelse($voters as $voter)
    <a class="canvass-voter-row" href="{{ route('voters.edit', $voter) }}" aria-label="{{ filled($voter->phone) ? 'View completed voter' : 'Capture phone number for' }} {{ $voter->full_name ?: 'Name not recorded' }}">
        @if($voter->photo_path)<img src="{{ route('voters.photo', $voter) }}" alt="Photo of {{ $voter->full_name }}" width="58" height="72" loading="lazy">@else<span class="canvass-avatar" aria-label="Photo not available">{{ mb_substr($voter->full_name ?: '?', 0, 1) }}</span>@endif
        <div class="canvass-voter-text"><div><h3>{{ $voter->full_name ?: 'Name not recorded' }}</h3><span class="canvass-badge {{ filled($voter->phone) ? 'canvass-badge-green' : 'canvass-badge-red' }}">{{ filled($voter->phone) ? 'Number Collected' : 'Not Yet Collected' }}</span></div><p>VIN: {{ $voter->vin ?: 'Not recorded' }}</p></div><x-canvass-icon name="arrow" class="canvass-row-arrow"/>
    </a>
@empty
    <div class="canvass-empty"><x-canvass-icon name="search"/><h2>No voters found</h2><p>Try another name, VIN or filter. Only your assigned polling unit appears here.</p><a href="{{ route('coordinator.register') }}">Reset filters</a></div>
@endforelse
</section>
<div class="voter-pagination">{{ $voters->links('pagination::simple-bootstrap-4') }}</div>
@endsection
