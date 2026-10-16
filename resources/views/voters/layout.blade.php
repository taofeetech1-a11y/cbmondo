@extends('coordinator.layout')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/voters.css') }}">
@endpush
@section('content')
    @php($privateVoterPage = request()->routeIs('voters.index', 'voters.edit'))
    @if ($privateVoterPage)
        <div class="voter-print-notice">Confidential voter information. Printing and PDF export are restricted.</div>
        <div class="voter-private" data-watermark="CONFIDENTIAL · Account #{{ auth('coordinator')->id() }}">
        <div class="voter-privacy-notice">Confidential voter information · Account #{{ auth('coordinator')->id() }}. Do not copy, capture or share voter details.</div>
    @endif
    <nav class="voter-nav" aria-label="Voter register navigation">
        <a href="{{ route('voters.index') }}" @if(request()->is('voters')) aria-current="page" @endif>Voter directory</a>
        <a href="{{ route('voters.import') }}" @if(request()->is('voters/import')) aria-current="page" @endif>Import register</a>
        <a class="voter-profile-link" href="{{ route('coordinator.profile') }}">My profile ↗</a>
    </nav>
    @yield('voter-content')
    @if ($privateVoterPage)
        </div>
    @endif
@endsection
