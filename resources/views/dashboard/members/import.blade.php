@extends('dashboard.app')
@section('title', 'Import members — CBM Ondo')
@section('content')
<div class="member-layout import-page">
    <a class="btn btn-outline" href="{{ route('membership.index') }}">← Back to members</a>
    <div class="page-head"><div><span class="eyebrow">GROW THE MEMBERSHIP</span><h1>Import members</h1><p>Prepare your file, review every record, then confirm the import.</p></div></div>
    @if (session('status'))
        <div class="member-notice" role="status">{{ session('status') }} <a href="{{ route('membership.index') }}">View members →</a></div>
    @endif
    @if ($errors->any())
        <div class="member-notice member-error" role="alert"><strong>Nothing was imported. Please review:</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <section class="panel import-panel">
        <h2>1. Prepare your file</h2>
        <p>Download a blank template. Use one worksheet with up to <strong>500 data rows</strong>, or a comma-separated UTF-8 CSV. Maximum file size: <strong>2 MB</strong>. Older .xls files must be saved as .xlsx first.</p>
        <div class="import-actions">
            <a class="btn btn-outline" href="{{ route('membership.import.template', 'xlsx') }}">Excel template ↓</a>
            <a class="btn btn-outline" href="{{ route('membership.import.template', 'csv') }}">CSV template ↓</a>
            <a class="btn btn-outline" href="{{ route('membership.import.locations') }}">Location reference ↓</a>
        </div>
        <details><summary>Column guide and accepted values</summary>
            <ul class="import-guide">
                <li><strong>name:</strong> required, up to 30 characters. <strong>phone:</strong> valid Nigerian number; format the cell as Text to preserve the leading zero. <strong>email:</strong> optional.</li>
                <li><strong>gender:</strong> male or female. <strong>age_range:</strong> 18-24, 25-34, 35-44, 45-54, 55-64 or 65+.</li>
                <li><strong>has_voters_card, support_us, want_to_be_contacted:</strong> yes or no. Record the member’s actual preferences; do not leave consent blank.</li>
                <li><strong>lga, ward, pu:</strong> numeric IDs from the location reference, not names or electoral codes. Every member needs an LGA.</li>
                <li>With a voter card: ward and pu must belong to the selected location, and <strong>same_address</strong> must be yes or no. Without a voter card: leave ward, pu and same_address blank.</li>
                <li>Keep all 12 headers. Paste values only; formulas are not accepted. Existing members are flagged as duplicates. New CBM IDs and membership codes are assigned when saved; registration dates use the import time.</li>
            </ul>
        </details>
    </section>
    <section class="panel import-panel">
        <h2>2. Upload and review</h2>
        <form method="POST" action="{{ route('membership.import.preview') }}" enctype="multipart/form-data" class="import-actions">
            @csrf
            <div class="field"><label for="import-file">Excel or CSV file</label><input id="import-file" type="file" name="file" accept=".xlsx,.csv" required></div>
            <button class="btn btn-primary" type="submit">Preview import</button>
        </form>
        <p class="member-help">Uploading does not save members. A new preview replaces the previous one and expires after 20 minutes.</p>
    </section>
    @if ($preview)
        <section class="panel import-panel">
            <h2>3. Confirm your import</h2>
            <p class="import-filename">{{ $preview['filename'] }} · Preview expires at {{ $preview['expires'] }} {{ config('app.timezone') }}.</p>
            <div class="import-summary" role="status">
                <span><strong>{{ count($preview['records']) }}</strong> total rows</span>
                <span><strong>{{ count($preview['records']) - count($preview['errors']) }}</strong> ready</span>
                <span><strong>{{ count($preview['errors']) }}</strong> rows need correction</span>
            </div>
            @if ($preview['errors'])
                <p class="field-error">Correct the marked rows in your file and upload it again. The entire batch must pass before any members can be saved.</p>
            @else
                <p>Review the normalized contact details below. Confirm to add all {{ count($preview['records']) }} members. Contacts and locations will be checked again before saving.</p>
                <form method="POST" action="{{ route('membership.import.store') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <button class="btn btn-primary" type="submit">Confirm import of {{ count($preview['records']) }} members</button>
                </form>
            @endif
            <div class="import-table-wrap" tabindex="0" role="region" aria-label="Import preview. Scroll horizontally for all columns.">
                <table class="import-table"><thead><tr><th>Row</th><th>Validation</th>@foreach ($columns as $column)<th>{{ str_replace('_', ' ', $column) }}</th>@endforeach</tr></thead>
                <tbody>@foreach ($preview['records'] as $row => $record)
                    <tr><th scope="row">{{ $row }}</th><td class="import-validation">@if (isset($preview['errors'][$row]))<ul class="field-error">@foreach ($preview['errors'][$row] as $issue)<li>{{ $issue }}</li>@endforeach</ul>@else<span class="import-ready">Ready</span>@endif</td>@foreach ($columns as $column)<td>{{ $record[$column] ?? '—' }}</td>@endforeach</tr>
                @endforeach</tbody></table>
            </div>
        </section>
    @endif
</div>
@endsection
