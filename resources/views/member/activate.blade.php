@extends('member.layout')
@section('title', 'Activate your membership — CBM Ondo')
@section('content')
<section class="panel coordinator-login">
    <span class="eyebrow">FIRST TIME SIGNING IN</span>
    <h1>Activate your membership</h1>
    @if($step === 'phone')
        <p>Enter the phone number you used when registering.</p>
        <form class="member-form" method="POST" action="{{ route('member.activate.phone') }}">@csrf
            <div class="field member-full"><label for="phone">Registered phone number</label><input id="phone" name="phone" type="tel" autocomplete="tel" value="{{ old('phone') }}" required autofocus></div>
            <button class="btn btn-primary member-full" type="submit">Find my membership</button>
        </form>
    @elseif($step === 'name')
        <p>Enter your full name exactly as you registered it.</p>
        <form class="member-form" method="POST" action="{{ route('member.activate.name') }}">@csrf
            <div class="field member-full"><label for="name">Full name</label><input id="name" name="name" autocomplete="name" value="{{ old('name') }}" maxlength="255" required autofocus></div>
            <button class="btn btn-primary member-full" type="submit">Continue</button>
        </form>
    @elseif($step === 'choose')
        <h2>Which profile belongs to you?</h2>
        <p>We found memberships with the same name. Choose your profile using its reference and masked phone number. Similar names do not mean a profile is fake.</p>
        <form class="member-form" method="POST" action="{{ route('member.activate.choose') }}">@csrf
            <fieldset class="member-full"><legend>Select your membership</legend>
                @foreach($candidates as $candidate)
                    <p><label><input type="radio" name="membership_id" value="{{ $candidate->id }}" required> {{ $candidate->name }} · {{ $candidate->cbm_id }} · Phone ending {{ substr(\App\Support\MembershipContact::phone($candidate->phone), -4) }}</label></p>
                @endforeach
                <p><label><input type="radio" name="membership_id" value="skip" required> Skip — keep the membership found with my phone number</label></p>
            </fieldset>
            <p class="member-full">Other matching records will be flagged for review. No profiles will be deleted.</p>
            <button class="btn btn-primary member-full" type="submit">Continue to password</button>
        </form>
    @else
        <h2>Create your password</h2>
        <p>Use at least 12 characters, including letters and numbers. You will use your original phone number to sign in.</p>
        <form class="member-form" method="POST" action="{{ route('member.activate.password') }}">@csrf
            <div class="field member-full"><label for="password">New password</label><input id="password" name="password" type="password" autocomplete="new-password" minlength="12" required autofocus></div>
            <div class="field member-full"><label for="password_confirmation">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="12" required></div>
            <button class="btn btn-primary member-full" type="submit">Create password</button>
        </form>
    @endif
    <p><a href="{{ route('member.login') }}">Already activated? Sign in</a></p>
    @if($step !== 'phone')
        <form method="POST" action="{{ route('member.activate.restart') }}">@csrf<button class="btn btn-outline" type="submit">Start again</button></form>
    @endif
</section>
@endsection
