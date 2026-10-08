@extends('dashboard.app')
@push('styles')<link rel="stylesheet" href="{{ asset('css/executives.css') }}">@endpush
@section('content')
<div class="executive-pages">
    <nav class="executive-nav" aria-label="Executives navigation">
        <a class="btn btn-outline" href="{{ route('executives.index') }}" @if(request()->routeIs('executives.index')) aria-current="page" @endif>Executives dashboard</a>
        <a class="btn btn-primary" href="{{ route('executives.create') }}" @if(request()->routeIs('executives.create')) aria-current="page" @endif>Assign executive</a>
        <a class="btn btn-outline" href="{{ route('executive-positions.index') }}" @if(request()->routeIs('executive-positions.*')) aria-current="page" @endif>Manage positions</a>
    </nav>
    @if(session('status'))<div class="member-notice" role="status">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="member-notice member-error" role="alert"><strong>Please correct the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @yield('executive-content')
</div>
@endsection
