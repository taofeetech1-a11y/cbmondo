@extends('voters.layout')
@section('title', 'Voter import conflicts — CBM Ondo')
@section('voter-content')
<div class="page-head voter-page-head"><div><span class="eyebrow">SUPER ADMIN REVIEW</span><h1>Import conflicts</h1><p>These incoming records were set aside. The existing voters, phone numbers and polling units were kept unchanged.</p></div></div>
<p>{{ number_format($conflicts->total()) }} records awaiting review. No records are moved automatically.</p>
@forelse($conflicts as $conflict)
<section class="panel">
    <h2>{{ $conflict->record['full_name'] ?? 'Name not recorded' }}</h2>
    <p>Conflict #{{ $conflict->id }} · Import #{{ $conflict->voter_register_import_id ?? 'Not recorded' }} · {{ $conflict->import?->user?->name ?? 'Uploader not recorded' }}</p>
    <p>{{ $conflict->reason === 'vin_in_another_unit' ? 'VIN already registered in another polling unit' : 'Record position conflicts with an existing voter' }}</p>
    @if($conflict->photo_path)<img class="voter-portrait" src="{{ route('voters.conflicts.photo', $conflict) }}" alt="Incoming voter photo" width="64" height="80" loading="lazy">@endif
    <dl>
        <dt>Incoming VIN</dt><dd>{{ $conflict->vin ?? 'Not recorded' }}</dd>
        <dt>Incoming polling unit</dt><dd>{{ $conflict->pollingUnit->delimitation_code }} · {{ $conflict->pollingUnit->name }}</dd>
        <dt>Polling-unit name printed in the PDF</dt><dd>{{ $conflict->record['polling_unit'] ?? 'Not recorded' }}</dd>
        <dt>Existing voter</dt><dd>{{ $conflict->existingVoter?->full_name ?? 'Not recorded' }} · #{{ $conflict->existing_voter_id ?? 'Not recorded' }}</dd>
        <dt>Existing polling unit at import</dt><dd>{{ $conflict->existingPollingUnit?->delimitation_code ?? 'Not recorded' }} · {{ $conflict->existingPollingUnit?->name ?? 'Not recorded' }}</dd>
        @foreach(['serial' => 'Incoming serial number', 'surname' => 'Surname', 'other_names' => 'Other names', 'gender' => 'Gender', 'age' => 'Age', 'occupation' => 'Occupation', 'state' => 'State', 'lga' => 'LGA', 'registration_area' => 'Ward'] as $field => $label)
            <dt>{{ $label }}</dt><dd>{{ $conflict->record[$field] ?? 'Not recorded' }}</dd>
        @endforeach
    </dl>
</section>
@empty
<section class="panel"><h2>No import conflicts awaiting review</h2><p>Conflicting records will appear here while the rest of each PDF continues importing.</p></section>
@endforelse
{{ $conflicts->links('pagination::simple-bootstrap-4') }}
@endsection
