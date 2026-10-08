@extends('dashboard.executives.layout')
@section('title', 'Assign Executive — CBM Ondo')
@section('executive-content')
<div class="page-head"><div><span class="eyebrow">MEMBER APPOINTMENTS</span><h1>Assign an executive</h1><p>Find a member using their full CBM ID. Positions are limited to their registered location.</p></div></div>
<form class="panel member-form" method="GET" action="{{ route('executives.create') }}"><div class="field member-full"><label for="executive-cbm-id">CBM ID</label><input id="executive-cbm-id" name="cbm_id" value="{{ $cbmId }}" placeholder="CBM-ON-…" maxlength="255" required></div><button class="btn btn-primary" type="submit">Find member</button></form>
@if($cbmId !== '' && ! $member)<div class="member-notice" role="status">{{ $ambiguous ? 'More than one member uses this CBM ID. Correct the duplicate IDs before assigning a position.' : 'No member found. Check the complete CBM ID and try again.' }}</div>@endif
@if($member)
<section class="panel"><h2>{{ $member->name }}</h2><dl class="member-details">@foreach(['CBM ID' => $member->cbm_id, 'Gender' => ucfirst($member->gender), 'LGA' => $member->lgaInfo?->name, 'Ward' => $member->wardInfo?->name, 'Polling unit' => $member->puInfo?->name] as $label => $value)<div><dt>{{ $label }}</dt><dd>{{ $value ?: 'Not registered' }}</dd></div>@endforeach</dl>
<h3>Current appointments</h3><ul>@forelse($member->executiveAssignments as $assignment)<li>{{ \App\Models\ExecutivePosition::LEVELS[$assignment->position->level] }}: {{ $assignment->position->name }}</li>@empty<li>No current appointments.</li>@endforelse</ul>
<p class="member-help">A member can hold one local appointment (LGA, ward or polling unit) plus one state appointment. Members without a ward or polling unit can only be assigned at LGA or state level.</p>
<form method="POST" action="{{ route('executives.store') }}" class="member-form">@csrf<input type="hidden" name="membership_id" value="{{ $member->id }}"><div class="field member-full"><label for="executive-position">Executive position</label><select id="executive-position" name="executive_position_id" required><option value="">Select a vacant position</option>
@foreach($positions as $position)
    @php($group = $position->level === 'state' ? 'state' : 'local')
    @php($alreadyAssigned = $member->executiveAssignments->contains('assignment_group', $group))
    <option value="{{ $position->id }}" @disabled($position->assignments->isNotEmpty() || $alreadyAssigned) @selected(old('executive_position_id') == $position->id)>{{ \App\Models\ExecutivePosition::LEVELS[$position->level] }} · {{ $position->name }} · {{ $position->level === 'lga' ? $member->lgaInfo?->name : $position->locationLabel() }}{{ $position->assignments->isNotEmpty() ? ' — Occupied' : ($alreadyAssigned ? ' — Member already holds a '.$group.' role' : ' — Vacant') }}</option>
@endforeach
</select></div>@if($positions->isEmpty())<p>No positions have been added for this member’s locations. Use Manage positions to add them first.</p>@endif<div class="member-form-actions member-full"><button class="btn btn-primary" type="submit" @disabled($positions->isEmpty())>Assign position</button></div></form></section>
@endif
@endsection
