@extends('member.layout')
@section('title', 'My membership — CBM Ondo')
@section('content')
<section class="panel">
    <span class="eyebrow">MY MEMBERSHIP</span>
    <h1>{{ $membership->name }}</h1>
    <p>Your personal CBM Ondo membership profile.</p>
    <dl class="member-form">
        @foreach(['Membership reference' => $membership->cbm_id, 'Login phone number' => $account->phone, 'Registered phone number' => $membership->phone, 'Email' => $membership->email, 'LGA' => $membership->lgaInfo?->name, 'Ward' => $membership->wardInfo?->name, 'Polling unit' => $membership->puInfo?->name] as $label => $value)
            <div class="member-full"><dt>{{ $label }}</dt><dd>{{ $value ?: 'Not recorded' }}</dd></div>
        @endforeach
    </dl>
    <p>Need to correct your details? <a href="{{ route('contact.page') }}">Contact support</a>.</p>
</section>
@endsection
