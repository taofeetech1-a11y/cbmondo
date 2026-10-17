@extends('coordinator.layout')
@section('title', 'My polling unit — CBM Ondo')
@section('content')
<div class="page-head"><div><span class="eyebrow">YOUR UNIT, AT A GLANCE</span><h1>Welcome, {{ $member->name }}</h1><p>Your polling unit workspace.</p></div></div>
@if (! $agent->password_changed_at)
    <div class="member-notice">Secure your account by changing the default password. <a href="{{ route('coordinator.profile') }}">Go to my profile →</a></div>
@endif
<section class="coordinator-unit">
    <span class="eyebrow">{{ $member->lgaInfo?->name ?? 'LGA not recorded' }} / {{ $member->wardInfo?->name ?? 'Ward not recorded' }}</span>
    <h2>{{ $member->puInfo?->name ?? 'Polling unit not assigned' }}</h2>
    <p>{{ $member->puInfo ? 'Your workspace follows the polling unit on your membership.' : 'Ask a staff administrator to complete your membership location.' }}</p>
    <span class="coordinator-status">Register not connected yet</span>
</section>
<div class="coordinator-metrics" aria-label="Register statistics">
    @foreach (['Total voters', 'Numbers collected', 'Numbers missing', 'Contact permission: Yes'] as $label)
        <section class="coordinator-metric"><h2>{{ $label }}</h2><strong aria-label="Not available">—</strong><span>Awaiting voter register</span></section>
    @endforeach
</div>
<div class="coordinator-panels">
    <section class="panel"><span class="eyebrow">VOTER REGISTER</span><h2>Your unit’s contact records</h2><p>View imported voters and collect phone numbers for your assigned polling unit.</p><a class="btn btn-primary" href="{{ route('coordinator.register') }}">Open voter register →</a></section>
    <section class="panel"><span class="eyebrow">YOUR ACCOUNT</span><h2>Make your profile yours</h2><p>Upload a photo or take a selfie, check your membership location, and change your password.</p><a class="btn btn-outline" href="{{ route('coordinator.profile') }}">Manage my profile →</a></section>
</div>
<section class="panel"><h2>Recent activity</h2><p>No register activity is available yet.</p></section>
@endsection
