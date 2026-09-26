@extends('dashboard.app')
@section('title', 'Update member — CBM Ondo')
@section('content')
<div class="member-layout">
    <a class="btn btn-outline" href="{{ route('membership.index', $filters) }}">← Back to members</a>
    <div class="page-head"><div><span class="eyebrow">EDIT MEMBER PROFILE</span><h1>Update {{ $member->name }}</h1><p>CBM ID: {{ $member->cbm_id }}</p></div></div>
    @if ($errors->any())
        <div class="member-notice member-error" role="alert"><strong>Please correct the following:</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <form class="panel member-form" method="POST" action="{{ route('membership.update', ['membership' => $member, 'filters' => $filters]) }}" id="member-edit-form">
        @csrf
        @method('PATCH')
        <h2 class="member-full">Personal details</h2>
        @foreach (['name' => ['Full name', 'text', 30], 'phone' => ['Phone number', 'tel', 18], 'email' => ['Email address (optional)', 'email', 50]] as $field => [$label, $type, $length])
            <div class="field"><label for="member-{{ $field }}">{{ $label }}</label><input id="member-{{ $field }}" name="{{ $field }}" type="{{ $type }}" maxlength="{{ $length }}" value="{{ old($field, $member->$field) }}" @required($field !== 'email') @error($field) aria-invalid="true" aria-describedby="error-{{ $field }}" @enderror>@error($field)<span class="field-error" id="error-{{ $field }}">{{ $message }}</span>@enderror</div>
        @endforeach
        @foreach (['gender' => ['Gender', ['male' => 'Male', 'female' => 'Female']], 'age_range' => ['Age range', ['18-24' => '18–24', '25-34' => '25–34', '35-44' => '35–44', '45-54' => '45–54', '55-64' => '55–64', '65+' => '65+']]] as $field => [$label, $options])
            <div class="field"><label for="member-{{ $field }}">{{ $label }}</label><select id="member-{{ $field }}" name="{{ $field }}" required>@foreach ($options as $value => $text)<option value="{{ $value }}" @selected(old($field, $member->$field) === $value)>{{ $text }}</option>@endforeach</select></div>
        @endforeach
        <h2 class="member-full">Electoral location</h2>
        <p class="member-help member-full">The CBM ID stays unchanged. Changing the polling unit assigns a new location-based membership code. Selecting “No voter card” clears the ward, polling unit and location codes.</p>
        <div class="field"><label for="member-card">Has a voter card?</label><select id="member-card" name="has_voters_card" required>@foreach (['yes' => 'Yes', 'no' => 'No'] as $value => $label)<option value="{{ $value }}" @selected(old('has_voters_card', $member->ward !== null ? 'yes' : 'no') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="field"><label for="member-lga">Local Government (LGA)</label><select id="member-lga" name="lga" required><option value="">Select LGA</option>@foreach ($lgas as $lga)<option value="{{ $lga->id }}" @selected((string) old('lga', $member->lga) === (string) $lga->id)>{{ $lga->name }}</option>@endforeach</select></div>
        <div class="field"><label for="member-ward">Ward</label><select id="member-ward" name="ward" data-url="{{ url('/location/wards') }}"><option value="">Select ward</option>@foreach ($wards as $ward)<option value="{{ $ward->id }}" @selected((string) old('ward', $member->ward) === (string) $ward->id)>{{ $ward->name }}</option>@endforeach</select></div>
        <div class="field"><label for="member-pu">Polling unit</label><select id="member-pu" name="pu" data-url="{{ url('/location/pollingunits') }}"><option value="">Select polling unit</option>@foreach ($pollingUnits as $unit)<option value="{{ $unit->id }}" @selected((string) old('pu', $member->pu) === (string) $unit->id)>{{ $unit->name }}</option>@endforeach</select></div>
        <p id="location-feedback" class="field-error member-full" role="status"></p>
        <h2 class="member-full">Preferences</h2>
        @foreach (['support_us' => 'Supports the movement?', 'want_to_be_contacted' => 'Wants to be contacted?', 'same_address' => 'Lives at voter-card location?'] as $field => $label)
            <div class="field"><label for="member-{{ $field }}">{{ $label }}</label><select id="member-{{ $field }}" name="{{ $field }}" required>@foreach (['yes' => 'Yes', 'no' => 'No'] as $value => $text)<option value="{{ $value }}" @selected(old($field, $member->$field) === $value)>{{ $text }}</option>@endforeach</select></div>
        @endforeach
        <div class="member-form-actions member-full"><a class="btn btn-outline" href="{{ route('membership.index', $filters) }}">Cancel</a><button type="submit" class="btn btn-primary">Save changes</button></div>
    </form>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/member-edit.js') }}" defer></script>
@endpush
