@extends('dashboard.app')
@section('title', 'My account — CBM Ondo')
@push('styles')
    @vite('resources/css/app.css')
@endpush
@push('scripts')
    @vite('resources/js/app.js')
@endpush
@section('content')
    <div class="account-workspace">
        <div class="page-head"><div><span class="eyebrow">YOUR WORKSPACE</span><h1>My account</h1><p>Keep your details up to date and your account secure.</p></div></div>
        <div class="account-panels">
            <div class="panel">
                <div class="max-w-xl">
                    @can('update-profile')
                        @include('profile.partials.update-profile-information-form')
                    @else
                        <h2>Account details</h2>
                        <p>Name: {{ $user->name }}</p>
                        <p>Email: {{ $user->email }}</p>
                        <p>Your account has view-only access. Ask a super admin to change these details.</p>
                    @endcan
                </div>
            </div>

            <div class="panel">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            @can('update-profile')<div class="panel account-danger">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>@endcan
        </div>
    </div>
@endsection
