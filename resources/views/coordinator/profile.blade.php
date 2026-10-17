@extends('coordinator.layout')
@section('title', 'My coordinator-agent profile — CBM Ondo')
@section('content')
<div class="page-head"><div><span class="eyebrow">COORDINATOR-AGENT</span><h1>My profile</h1><p>Manage your photo and sign-in password.</p></div></div>
@if (! $agent->password_changed_at)
    <div class="member-notice" role="status">Set your own password below before accessing your polling unit. Your temporary password expires after 24 hours.</div>
@endif
<section class="panel coordinator-summary">
    @if ($agent->password_changed_at && $agent->photo_path)
        <img class="coordinator-photo" src="{{ route('coordinator.photo') }}" alt="Your profile photo">
    @else
        <div class="coordinator-photo coordinator-initial" aria-label="No profile photo">{{ mb_substr($member->name, 0, 1) }}</div>
    @endif
    <div class="coordinator-identity"><span class="eyebrow">YOUR MEMBERSHIP</span><h2>{{ $member->name }}</h2><p class="coordinator-id">{{ $member->cbm_id }}</p>
        <dl class="coordinator-location">
            <div><dt>LGA</dt><dd>{{ $member->lgaInfo?->name ?? 'Not recorded' }}</dd></div>
            <div><dt>Ward</dt><dd>{{ $member->wardInfo?->name ?? 'Not recorded' }}</dd></div>
            <div><dt>Polling unit</dt><dd>{{ $member->puInfo?->name ?? 'Not recorded' }}</dd></div>
        </dl>
    </div>
</section>
<div class="coordinator-panels">
    @if($agent->password_changed_at)
    <section class="panel">
        <h2>Profile photo</h2>
        <p>Choose a picture from your phone or take a new selfie.</p>
        <form id="coordinator-photo-form" class="member-form" method="POST" enctype="multipart/form-data" action="{{ route('coordinator.photo.update') }}">
            @csrf
            <div class="field member-full">
                <label for="photo">Choose a photo</label>
                <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" aria-describedby="photo-help" required>
                <div id="photo-camera-controls" class="photo-camera-controls" hidden>
                    <button id="take-selfie" class="btn btn-outline" type="button">Take a selfie</button>
                    <input id="selfie" type="file" accept="image/*" capture="user" aria-label="Take a selfie with your front camera" hidden>
                    <p class="photo-help">Your phone may ask you to choose a camera. Switch to the front camera if needed.</p>
                </div>
                <p id="photo-help" class="photo-help">JPG, PNG or WebP. Maximum upload: 2 MB. Large pictures are resized automatically when supported.</p>
                <div id="photo-preview-wrap" class="photo-preview-wrap" hidden>
                    <img id="photo-preview" alt="Preview of your selected profile photo">
                    <p>Preview — your photo is saved only when you tap Save photo.</p>
                    <button id="clear-photo" type="button" class="btn btn-outline">Remove selection</button>
                </div>
                <p id="photo-feedback" class="photo-help" role="status" aria-live="polite"></p>
                @error('photo', 'photo')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <button class="btn btn-primary member-full" type="submit">Save photo</button>
        </form>
    </section>
    @endif
    <section class="panel">
        <h2>{{ $agent->password_changed_at ? 'Change password' : 'Set your own password' }}</h2>
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
<script src="{{ asset('js/coordinator-photo.js') }}" defer></script>
@endsection
