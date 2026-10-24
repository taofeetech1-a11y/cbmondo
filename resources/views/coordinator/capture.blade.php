@extends('coordinator.canvass-layout')
@section('title', ($readOnly ? 'Voter details' : 'Capture Phone Number').' — CBM Ondo')
@section('screen-title', $readOnly ? 'Voter Details' : 'Capture Phone Number')
@section('back-url', route('coordinator.register'))
@section('content')
<section class="canvass-person" aria-label="Confirm voter details">
    @if($voter->photo_path)<img src="{{ route('voters.photo', $voter) }}" alt="Photo of {{ $voter->full_name }}" width="100" height="128">@else<span class="canvass-person-placeholder" aria-label="Photo not available">{{ mb_substr($voter->full_name ?: '?', 0, 1) }}</span>@endif
    <div><h2>{{ $voter->full_name ?: 'Name not recorded' }}</h2><p>VIN: {{ $voter->vin ?: 'Not recorded' }}</p><p>Age: {{ $voter->age !== null ? $voter->age.' years' : 'Not recorded' }} <span aria-hidden="true">·</span> Gender: {{ $voter->gender ?: 'Not recorded' }}</p><p>Occupation: {{ $voter->occupation ?: 'Not recorded' }}</p></div>
</section>
<p class="canvass-unit-caption">{{ $voter->pollingUnit->name }} · {{ $voter->pollingUnit->delimitation_code }}</p>
@if($readOnly)
    <section class="canvass-readonly"><span class="canvass-badge canvass-badge-green">Number Collected</span><h2>This record is read-only</h2><p>Phone number: <strong>{{ $voter->phone }}</strong></p><dl>@foreach(['spoken_to' => 'Spoken To', 'apc_member' => 'APC Member', 'consent_given' => 'Consent Given'] as $field => $label)<div><dt>{{ $label }}</dt><dd>{{ $voter->$field === null ? 'Not recorded' : ($voter->$field ? 'Yes' : 'No') }}</dd></div>@endforeach</dl><a class="canvass-primary" href="{{ route('coordinator.register') }}">Back to register</a></section>
@else
    @if($errors->any())<div class="canvass-notice canvass-error" role="alert">Please correct the highlighted fields below.</div>@endif
    <form class="canvass-capture" method="POST" action="{{ route('voters.update', $voter) }}">@csrf @method('PATCH')
        <label class="canvass-phone-label" for="voter-phone">Phone Number <span aria-hidden="true">*</span></label><div class="canvass-phone-input"><x-canvass-icon name="phone"/><input id="voter-phone" name="phone" type="tel" autocomplete="tel" value="{{ old('phone') }}" placeholder="Enter phone number" maxlength="30" required @error('phone') aria-invalid="true" aria-describedby="error-phone" @enderror></div>
        @error('phone')<p class="canvass-field-error" id="error-phone">{{ $message }}</p>@enderror
        <div class="canvass-checks">
        @foreach(['spoken_to' => ['Spoken To', 'I have spoken to this voter'], 'apc_member' => ['APC Member', 'Voter confirmed APC membership'], 'consent_given' => ['Consent Given', 'Voter agreed to be contacted. Unchecked records no consent.']] as $field => [$label, $help])
            <input type="hidden" name="{{ $field }}" value="0"><label class="canvass-check"><input type="checkbox" name="{{ $field }}" value="1" @checked(old($field, false))><span><strong>{{ $label }}</strong><small>{{ $help }}</small>@error($field)<span class="canvass-field-error">{{ $message }}</span>@enderror</span></label>
        @endforeach
        </div>
        <p class="canvass-footnote">Confirm the voter before saving. Saved records become read-only. Each voter earns collection credit once.</p><button class="canvass-primary" type="submit">Save</button>
    </form>
@endif
@endsection
