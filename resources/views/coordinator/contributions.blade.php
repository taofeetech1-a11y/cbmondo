@extends('coordinator.canvass-layout')
@section('title', 'My Contributions — CBM Ondo')
@section('screen-title', 'My Contributions')
@section('back-url', route('coordinator.dashboard'))
@section('content')
@include('coordinator.unit-strip', ['total' => $unit->voters_count])
<section class="canvass-contribution"><span class="canvass-metric-icon"><x-canvass-icon name="star"/></span><h2>Your contribution</h2><strong>{{ number_format($coordinatorPoints) }}</strong><p>points earned · Rank #{{ $coordinatorRank }} in your unit</p><p class="canvass-footnote">1 completed voter = 1 point. Each voter earns credit once. Equal scores share the same rank. Previously saved numbers without attribution earn no points.</p></section>
<section class="canvass-leaderboard"><h2>Unit leaderboard</h2><ol>@foreach($unitLeaderboard as $participant)<li @if($participant->id === auth()->id()) class="is-you" @endif><span>#{{ 1 + $unitLeaderboard->where('points', '>', $participant->points)->count() }}</span><strong>{{ $participant->membership->name }} @if($participant->id === auth()->id())<small>(you)</small>@endif</strong><b>{{ number_format($participant->points) }} <small>pts</small></b></li>@endforeach</ol></section>
@endsection
