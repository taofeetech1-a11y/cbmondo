@extends('member.layout')
@section('title', 'Member sign in — CBM Ondo')
@section('content')
<section class="panel coordinator-login">
    <span class="eyebrow">YOUR MEMBERSHIP</span>
    <h1>Welcome back</h1>
    <p>Sign in to view your membership profile.</p>
    <form class="member-form" method="POST" action="{{ route('member.login') }}">
        @csrf
        <div class="field member-full"><label for="phone">Phone number</label><input id="phone" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="username" required autofocus></div>
        <div class="field member-full"><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required></div>
        <button class="btn btn-primary member-full" type="submit">Sign in</button>
    </form>
    <p>First time here? <a href="{{ route('member.activate') }}">Activate your membership login</a></p>
    <p>Forgot your password? <a href="{{ route('contact.page') }}">Contact support</a>. Activation cannot reset an existing password.</p>
</section>
@endsection
