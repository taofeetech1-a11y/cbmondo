@extends('coordinator.layout')
@section('title', 'Voter register — CBM Ondo')
@section('content')
<div class="page-head"><div><span class="eyebrow">YOUR POLLING UNIT</span><h1>Voter register</h1><p>{{ $member->puInfo?->name ?? 'Polling unit not assigned' }}</p></div></div>
<section class="panel coordinator-empty">
    <span class="coordinator-status">Not connected yet</span>
    <h2>Your register will appear here</h2>
    <p>The PDF import feature is not connected yet. Once available, this page will show the voter register for your membership’s polling unit.</p>
    <p>No voter records or photos have been imported.</p>
    <a class="btn btn-outline" href="{{ route('coordinator.dashboard') }}">Back to Home</a>
</section>
@endsection
