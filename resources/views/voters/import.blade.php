@extends('voters.layout')
@section('title', 'Import voter register — CBM Ondo')
@section('voter-content')
<div class="page-head voter-page-head">
    <div><span class="eyebrow">GROW YOUR VOTER DIRECTORY</span><h1>Import a register</h1><p>One polling unit, one PDF. Choose its location to get started.</p></div>
    @can('view-voters')<a class="btn btn-outline" href="{{ route('voters.index') }}">← Voter directory</a>@endcan
</div>
@if ($errors->any())
    <div class="member-notice member-error" role="alert">Your register has not been imported. Please correct the highlighted fields below.</div>
@endif
<div class="voter-import-layout">
<form class="panel voter-import-form" method="POST" action="{{ route('voters.import') }}" enctype="multipart/form-data" id="voter-import-form">
    @csrf
    <fieldset class="voter-step"><legend><span class="voter-step-number">01</span> Choose the polling unit</legend>
    <p class="voter-help">All voters in this PDF will be linked to the polling unit you select.</p>
    <div class="field">
        <label for="import-lga">LGA</label>
        <select id="import-lga" name="lga" required>
            <option value="">Select LGA</option>
            @foreach ($lgas as $lga)
                <option value="{{ $lga->id }}" @selected(old('lga') == $lga->id)>{{ $lga->name }}</option>
            @endforeach
        </select>
        @error('lga') <span class="field-error" role="alert">{{ $message }}</span> @enderror
    </div>
    <div class="field">
        <label for="import-ward">Ward</label>
        <select id="import-ward" name="ward" required @disabled($wards->isEmpty()) data-url="{{ route('ward', ['lga' => '__id__']) }}">
            <option value="">Select ward</option>
            @foreach ($wards as $ward)
                <option value="{{ $ward->id }}" @selected(old('ward') == $ward->id)>{{ $ward->code }} — {{ $ward->name }}</option>
            @endforeach
        </select>
        @error('ward') <span class="field-error" role="alert">{{ $message }}</span> @enderror
    </div>
    <div class="field">
        <label for="import-polling-unit">Polling unit</label>
        <select id="import-polling-unit" name="polling_unit_id" required @disabled($pollingUnits->isEmpty()) data-url="{{ route('polling.unit', ['ward' => '__id__']) }}">
            <option value="">Select polling unit</option>
            @foreach ($pollingUnits as $unit)
                <option value="{{ $unit->id }}" @selected(old('polling_unit_id') == $unit->id)>{{ $unit->delimitation_code }} — {{ $unit->name }}</option>
            @endforeach
        </select>
        @error('polling_unit_id') <span class="field-error" role="alert">{{ $message }}</span> @enderror
    </div>
    <p id="import-location-feedback" class="field-error" role="status" aria-live="polite"></p>
    </fieldset>
    <fieldset class="voter-step voter-file-step"><legend><span class="voter-step-number">02</span> Add the PDF register</legend>
        <div class="voter-upload-box field">
            <span class="voter-file-icon" aria-hidden="true">PDF</span>
            <label for="import-pdf">Choose your polling-unit register</label>
            <p id="import-pdf-help" class="voter-help">PDF files only · Maximum size 50 MB</p>
            <input id="import-pdf" type="file" name="pdf" accept="application/pdf" aria-describedby="import-pdf-help" required>
        </div>
        @error('pdf') <p class="field-error" role="alert">{{ $message }}</p> @enderror
    </fieldset>
    <div class="voter-form-footer"><span>Check the location before importing.</span><button class="btn btn-primary" type="submit">Upload & import →</button></div>
</form>
<aside class="voter-import-aside">
    <section class="panel voter-guide"><span class="eyebrow">BEFORE YOU UPLOAD</span><h2>A quick check</h2>
        <ol><li><b>Match the location</b><p>Check that the LGA, ward and polling unit match the register you are uploading.</p></li><li><b>Use one PDF per unit</b><p>Each polling unit has its own register. Upload its PDF separately.</p></li><li><b>Review the directory</b><p>After extraction, your imported voter records appear in the voter directory.</p></li></ol>
    </section>
    <div class="voter-import-note"><b>Already imported this register?</b><p>Matching voter identification numbers update existing records instead of creating duplicates.</p></div>
</aside>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/voter-import.js') }}" defer></script>
@endpush
