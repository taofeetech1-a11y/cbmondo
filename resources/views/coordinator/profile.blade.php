@extends('coordinator.layout')
@section('title', 'My coordinator-agent profile — CBM Ondo')
@section('content')
<div class="page-head"><div><span class="eyebrow">COORDINATOR-AGENT</span><h1>My profile</h1><p>Manage your photo and sign-in password.</p></div></div>
@if (! $agent->password_changed_at)
    <div class="member-notice" role="status">You are using the shared default password. Change it below to secure your account.</div>
@endif
<section class="panel coordinator-summary">
    @if ($agent->photo_path)
        <img class="coordinator-photo" src="{{ route('coordinator.photo') }}" alt="Your profile photo">
    @else
        <div class="coordinator-photo coordinator-initial" aria-label="No profile photo">{{ mb_substr($member->name, 0, 1) }}</div>
    @endif
    <div><h2>{{ $member->name }}</h2><p>{{ $member->cbm_id }}</p><p>{{ $member->lgaInfo?->name ?? 'LGA not recorded' }} · {{ $member->wardInfo?->name ?? 'Ward not recorded' }} · {{ $member->puInfo?->name ?? 'Polling unit not recorded' }}</p></div>
</section>
<div class="coordinator-panels">
    <section class="panel">
        <h2>Profile photo</h2>
        <p>Choose a JPG, PNG or WebP image, up to 2 MB and 4096 × 4096 pixels.</p>
        <form class="member-form" method="POST" enctype="multipart/form-data" action="{{ route('coordinator.photo.update') }}">
            @csrf
            <div class="field member-full">
                <label for="photo">Upload photo</label>
                <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" required>
                @error('photo', 'photo')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <button class="btn btn-primary member-full" type="submit">Save photo</button>
        </form>
    </section>
    <section class="panel">
        <h2>Change password</h2>
        <p>Choose a new password with at least 12 characters.</p>
        <form class="member-form" method="POST" action="{{ route('coordinator.password') }}">
            @csrf
            @method('PUT')
            @foreach (['current_password' => 'Current password', 'password' => 'New password', 'password_confirmation' => 'Confirm new password'] as $field => $label)
                <div class="field member-full">
                    <label for="{{ $field }}">{{ $label }}</label>
                    <input id="{{ $field }}" name="{{ $field }}" type="password" autocomplete="{{ $field === 'current_password' ? 'current-password' : 'new-password' }}" required>
                    @error($field, 'password')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>
            @endforeach
            <button class="btn btn-primary member-full" type="submit">Update password</button>
        </form>
    </section>
</div>
@endsection
