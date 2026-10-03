@extends('dashboard.app')
@section('title', 'Add Exco — CBM Ondo')
@section('content')
<div class="member-layout">
    <a class="btn btn-outline" href="{{ route('excos.index') }}">← Back to Excos</a>
    <div class="page-head"><div><span class="eyebrow">EXECUTIVE MEMBERS</span><h1>Add Exco</h1><p>Find a registered member, review their details, then add them to Excos.</p></div></div>
    @if($errors->any())<div class="member-notice member-error" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <form method="GET" action="{{ route('excos.create') }}" class="panel member-form">
        <div class="field member-full"><label for="member-id">CBM ID or Member ID</label><input id="member-id" name="member_id" value="{{ $memberId }}" maxlength="255" placeholder="Enter the complete ID from the member's card" required><p class="member-help">Use the CBM ID (CBM-ON-…) or the full location-based Member ID.</p></div>
        <div class="member-form-actions member-full"><button class="btn btn-primary" type="submit">Find member</button></div>
    </form>
    @if($memberId !== '')
        @if($matches->isEmpty())<div class="panel empty-state" role="status"><h2>No member found</h2><p>Check the complete ID and try again. The person must already be registered.</p></div>
        @elseif($matches->count() > 1)<div class="member-notice member-error" role="alert">More than one member uses this ID. Resolve the duplicate membership IDs before adding an Exco.</div>
        @else
            @php($member = $matches->first())
            <section class="panel"><h2>{{ $member->name }}</h2><dl class="member-details">
                @foreach(['CBM ID' => $member->cbm_id, 'Member ID' => $member->cbm_delimitation_code, 'Gender' => ucfirst($member->gender), 'Phone' => $member->phone, 'LGA' => $member->lgaInfo?->name, 'Ward' => $member->wardInfo?->name, 'Polling unit' => $member->puInfo?->name] as $label => $value)<div><dt>{{ $label }}</dt><dd>{{ $value ?: 'Not assigned' }}</dd></div>@endforeach
            </dl>
            @if($member->exco)<div class="member-notice" role="status">This member is already an Exco.</div>
            @else<form method="POST" action="{{ route('excos.store') }}">@csrf<input type="hidden" name="membership_id" value="{{ $member->id }}"><p class="member-help">Their existing profile and IDs will be used. No duplicate membership will be created.</p><button class="btn btn-primary" type="submit">Add {{ $member->name }} to Excos</button></form>@endif
            </section>
        @endif
    @endif
</div>
@endsection
