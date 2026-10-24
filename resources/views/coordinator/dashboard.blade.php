@extends('coordinator.canvass-layout')
@section('title', 'Dashboard — CBM Ondo')
@section('screen-title', 'Dashboard')
@section('content')
@include('coordinator.unit-strip', ['total' => $stats['total']])
<p class="canvass-welcome">Welcome, {{ auth()->user()->membership->name }}. Here’s your unit’s progress.</p>
<section class="canvass-metrics" aria-label="Unit collection and consent statistics">
    @foreach([
        ['Collected', 'collected', 'phone', 'green', ['collection' => 'collected']],
        ['Not Yet Collected', 'pending', 'users', 'red', ['collection' => 'pending']],
        ['Consented', 'consented', 'check', 'green', ['consent' => 'given']],
        ['Not Consented', 'not_consented', 'ban', 'red', ['consent' => 'declined']],
    ] as [$label, $key, $icon, $tone, $filter])
        <a class="canvass-metric canvass-metric-{{ $tone }}" href="{{ route('coordinator.register', $filter) }}"><span class="canvass-metric-icon"><x-canvass-icon :name="$icon"/></span><h2>{{ $label }}</h2><strong>{{ number_format($stats[$key]) }}</strong><span class="canvass-metric-hint">View voters <span aria-hidden="true">↗</span></span></a>
    @endforeach
</section>
<p class="canvass-footnote">Consent not recorded: <a href="{{ route('coordinator.register', ['consent' => 'unknown']) }}">{{ number_format($stats['consent_unknown']) }} voters</a>. Unrecorded consent is excluded from both consent cards.</p>
<section class="canvass-mini-stats" aria-label="Conversations and membership"><div><span>Spoken to</span><strong>{{ number_format($stats['spoken_to']) }}</strong></div><div><span>APC members confirmed</span><strong>{{ number_format($stats['apc_members']) }}</strong></div></section>
<a class="canvass-primary" href="{{ route('coordinator.register') }}">Open polling unit register <x-canvass-icon name="arrow"/></a>
<section class="canvass-ward-overview" aria-label="Ward membership registrations">
    <div class="canvass-section-heading"><div><span>YOUR WARD · MEMBERSHIP</span><h2>{{ $unit->ward->name }}</h2></div><strong>{{ number_format($wardMemberTotal) }} <small>members</small></strong></div>
    <p>Membership registrations across all polling units in your ward.</p>
    <x-registration-trend :trend="$membershipTrend"/>
    <a class="canvass-text-link" href="{{ route('coordinator.memberships') }}">View ward members and phone numbers →</a>
</section>
@endsection
