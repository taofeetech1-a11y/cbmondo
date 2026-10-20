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
    <p class="voter-help">Match the full delimitation code on the PDF to the polling unit you select. The polling-unit name or address may be different.</p>
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
    <div class="voter-form-footer"><span>Check the location before importing.</span><button class="btn btn-primary" type="submit">Upload & queue →</button></div>
</form>
<aside class="voter-import-aside">
    <section class="panel voter-guide"><span class="eyebrow">BEFORE YOU UPLOAD</span><h2>A quick check</h2>
        <ol><li><b>Match the delimitation code</b><p>Use the full code, including state, LGA, ward and polling unit. Names and addresses can change; the code identifies the unit.</p></li><li><b>Use one PDF per unit</b><p>Each polling unit has its own register. Upload its PDF separately.</p></li><li><b>Review the directory</b><p>After extraction, your imported voter records appear in the voter directory.</p></li></ol>
    </section>
    <div class="voter-import-note"><b>Already imported this register?</b><p>Reimporting the same polling unit updates its records while preserving collected phone numbers and coordinator credit.</p></div>
</aside>
</div>
<section class="panel import-history" aria-label="PDF import history" data-import-history data-status-url="{{ route('voters.imports.status', ['page' => request('page', 1)]) }}">
    <div class="voter-results-heading"><h2>{{ auth()->user()->role === 'super_admin' ? 'Recent imports' : 'Your recent imports' }}</h2><span>Processing continues after you leave this page</span></div>
    <p class="voter-help">PDFs are processed one at a time to protect server resources. You can have up to {{ config('voter-imports.max_pending_per_user') }} pending imports. Status updates automatically while this page is open. Failed source PDFs are retained privately for {{ config('voter-imports.retention_days') }} days so you can retry.</p>
    <p class="voter-help" data-import-refresh-status role="status" aria-live="polite"></p>
    @forelse($imports as $import)
        <article class="import-history-row" data-import-id="{{ $import->id }}" data-import-status="{{ $import->status }}">
            <div><strong>#{{ $import->id }} · {{ $import->pollingUnit->delimitation_code }} · {{ $import->pollingUnit->name }}</strong><p>{{ $import->original_name }} · {{ $import->created_at->format('d M Y, H:i') }}@if(auth()->user()->role === 'super_admin') · {{ $import->user?->name ?? 'Former staff member' }}@endif</p></div>
            <div class="import-history-result"><span class="voter-status-badge" data-import-label>{{ ucfirst($import->status) }}</span><span data-import-count>{{ $import->voters_count !== null ? number_format($import->voters_count).' voters'.($import->conflicts_count > 0 ? ' · '.number_format($import->conflicts_count).' set aside for review' : '') : '' }}</span></div>
            <p class="field-error" data-import-error>{{ $import->error_message }}</p>
            <form method="POST" action="{{ route('voters.imports.retry', $import) }}" data-import-retry @if($import->status !== 'failed' || !$import->pdf_path) hidden @endif>@csrf<button class="btn btn-outline" type="submit">Retry import</button></form>
        </article>
    @empty
        <p class="voter-help">No imports yet. Your uploads and their results will appear here.</p>
    @endforelse
    {{ $imports->links('pagination::simple-bootstrap-4') }}
</section>
@endsection
@push('scripts')
<script src="{{ asset('js/voter-import.js') }}" defer></script>
@endpush
