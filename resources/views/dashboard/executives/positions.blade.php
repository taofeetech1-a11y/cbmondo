@extends('dashboard.executives.layout')
@section('title', 'Executive Positions — CBM Ondo')
@section('executive-content')
<div class="page-head"><div><span class="eyebrow">CONFIGURE YOUR LEADERSHIP</span><h1>Executive positions</h1><p>LGA positions are shared across all LGAs: add or rename a position once for every LGA. Each LGA has its own occupant for each position. Ward and polling-unit lists remain specific to their locations.</p></div></div>
<form class="filters" action="{{ route('executive-positions.index') }}" method="GET">
<div class="field"><label for="position-level">Level</label><select id="position-level" name="level" onchange="for (const name of ['lga','ward','pu']) { if(this.form.elements[name]) this.form.elements[name].value=''; } this.form.submit()">@foreach(\App\Models\ExecutivePosition::LEVELS as $value => $label)<option value="{{ $value }}" @selected($level === $value)>{{ $label }}</option>@endforeach</select></div>
@if(! in_array($level, ['state', 'lga']))
    @include('dashboard.executives.locations', ['positionLevel' => $level])
@endif
<button class="btn btn-primary" type="submit">Show positions</button>
</form>
@php($ready = in_array($level, ['state', 'lga']) || ($level === 'ward' && request()->filled(['lga','ward'])) || ($level === 'polling_unit' && request()->filled(['lga','ward','pu'])))
@if($ready)
<form method="POST" action="{{ route('executive-positions.store') }}" class="panel member-form">
@csrf<input type="hidden" name="level" value="{{ $level }}">@foreach(['lga','ward','pu'] as $field)<input type="hidden" name="{{ $field }}" value="{{ request($field) }}">@endforeach
<div class="field member-full"><label for="position-name">New position name</label><input id="position-name" name="name" value="{{ old('name') }}" placeholder="e.g. Chairperson" maxlength="100" required><p class="member-help">{{ $level === 'lga' ? 'This position will be available in every LGA, with one occupant per LGA.' : 'This creates a position at the selected level and location only. Each position can have one occupant.' }}</p></div><div class="member-form-actions member-full"><button class="btn btn-primary" type="submit">Add position</button></div>
</form>
@else<div class="member-notice">Choose the LGA, ward and polling unit required for this level, then select Show positions to add a position there.</div>@endif
<section class="table-panel"><h2>{{ \App\Models\ExecutivePosition::LEVELS[$level] }} positions</h2><p>{{ $positions->total() }} positions match this selection. Rename a position here; remove all its appointments before deleting it.</p>
<div class="table-scroll" role="region" aria-label="Executive positions" tabindex="0"><table><thead><tr><th scope="col">Position name</th><th scope="col">Location</th><th scope="col">Status</th><th scope="col">Actions</th></tr></thead><tbody>
@forelse($positions as $position)<tr><td><form method="POST" action="{{ route('executive-positions.update', $position) }}" class="executive-rename">@csrf @method('PATCH')<input name="name" value="{{ $position->name }}" maxlength="100" required aria-label="Rename {{ $position->name }}"><button class="btn btn-outline" type="submit">Save name</button></form></td><td>{{ $position->locationLabel() }}</td><td>{{ $level === 'lga' ? $position->assignments_count.' LGAs occupied' : ($position->assignments_count ? 'Occupied' : 'Vacant') }}</td><td><form method="POST" action="{{ route('executive-positions.destroy', $position) }}" onsubmit="return confirm('Delete this vacant executive position?')">@csrf @method('DELETE')<button class="btn btn-outline" type="submit" @disabled($position->assignments_count)>Delete position</button></form></td></tr>
@empty<tr><td colspan="4"><div class="empty-state"><h3>No positions added yet</h3><p>Select a location and add its position names above.</p></div></td></tr>@endforelse
</tbody></table></div><div class="table-foot">{{ $positions->links() }}</div></section>
@endsection
