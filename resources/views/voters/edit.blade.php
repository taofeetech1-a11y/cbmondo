@extends('voters.layout')
@section('title', 'Edit voter — CBM Ondo')
@section('voter-content')
<div class="page-head voter-page-head">
    <div><span class="eyebrow">VOTER DETAILS</span><h1>Edit voter</h1><p>{{ $voter->full_name }}</p></div>
    <a class="btn btn-outline" href="{{ route('voters.index') }}">← Voter directory</a>
</div>
@if ($errors->any())
    <div class="member-notice member-error" role="alert">Please correct the highlighted fields below.</div>
@endif
<form class="panel voter-import-form voter-edit-form" method="POST" action="{{ route('voters.update', $voter) }}">
    @csrf
    @method('PATCH')
    <h2>Personal details</h2>
    <div class="voter-edit-fields">
    @foreach (['full_name' => 'Full name', 'surname' => 'Surname', 'other_names' => 'Other names', 'vin' => 'Voter identification number', 'occupation' => 'Occupation', 'phone' => 'Phone number'] as $field => $label)
        <div class="field">
            <label for="voter-{{ $field }}">{{ $label }}</label>
            <input id="voter-{{ $field }}" name="{{ $field }}" type="{{ $field === 'phone' ? 'tel' : 'text' }}" value="{{ $field === 'phone' ? old('phone', $voter->phone) : $voter->$field }}" maxlength="{{ $field === 'phone' ? 30 : 255 }}" @required($field === 'phone') @readonly($field !== 'phone') @error($field) aria-invalid="true" aria-describedby="error-{{ $field }}" @enderror>
            @error($field)<span class="field-error" id="error-{{ $field }}">{{ $message }}</span>@enderror
        </div>
    @endforeach
        <div class="field"><label for="voter-gender">Gender</label><input id="voter-gender" name="gender" value="{{ $voter->gender }}" readonly></div>
        <div class="field"><label for="voter-age">Age</label><input id="voter-age" name="age" value="{{ $voter->age }}" readonly></div>
    </div>
    <p class="voter-help">Only the phone number can be changed here and it is required. Polling unit: <strong>{{ $voter->polling_unit ?: 'Not recorded' }}</strong>.</p>
    <div class="voter-form-footer"><a class="btn btn-outline" href="{{ route('voters.index') }}">Cancel</a><button class="btn btn-primary" type="submit">Save changes</button></div>
</form>
@endsection
