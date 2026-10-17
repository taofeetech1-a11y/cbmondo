@extends('coordinator.layout')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/voters.css') }}">
@endpush
@section('content')
    @php($privateVoterPage = request()->routeIs('voters.index', 'voters.edit'))
    @if ($privateVoterPage)
        <div class="voter-print-notice">Confidential voter information. Printing and PDF export are restricted.</div>
        <div class="voter-private" data-watermark="CONFIDENTIAL · Account #{{ auth()->id() }}">
        <div class="voter-privacy-notice">Confidential voter information · Account #{{ auth()->id() }}. Do not copy, capture or share voter details.</div>
    @endif
    <nav class="voter-nav" aria-label="Voter register navigation">
        @can('view-voters')<a href="{{ route('voters.index') }}" @if(request()->is('voters')) aria-current="page" @endif>Voter directory</a>@endcan
        @can('import-voters')<a href="{{ route('voters.import') }}" @if(request()->is('voters/import')) aria-current="page" @endif>Import register</a>@endcan
        @if(auth()->user() instanceof \App\Models\CoordinatorAgent)
            <a class="voter-profile-link" href="{{ route('coordinator.profile') }}">My profile ↗</a>
        @elseif(auth()->user()?->hasPermission('access-dashboard'))
            <a class="voter-profile-link" href="{{ route('dashboard') }}">Staff dashboard ↗</a>
        @endif
    </nav>
    @yield('voter-content')
    @if ($privateVoterPage)
        </div>
    @endif
@endsection
