@extends('coordinator.layout')
@section('title', 'Coordinator-agent sign in — CBM Ondo')
@section('content')
<section class="panel coordinator-login">
    <span class="eyebrow">MEMBER ACCESS</span>
    <h1>Coordinator-agent sign in</h1>
    <p>Use your CBM ID and the password provided when your account was created.</p>
    @if($errors->any())
        <div class="member-notice member-error" role="alert">{{ $errors->first() }}</div>
    @endif
    <form class="member-form" method="POST" action="{{ route('coordinator.login') }}">
        @csrf
        <div class="field member-full">
            <label for="cbm_id">CBM ID</label>
            <input id="cbm_id" name="cbm_id" value="{{ old('cbm_id') }}" autocomplete="username" autocapitalize="none" spellcheck="false" required autofocus>
        </div>
        <div class="field member-full">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
        </div>
        <button class="btn btn-primary member-full" type="submit">Sign in</button>
    </form>
    <p>Accounts are created by a super admin from an existing CBM membership.</p>
    <a href="{{ route('new.homepage') }}">Back to website</a>
</section>
@endsection
