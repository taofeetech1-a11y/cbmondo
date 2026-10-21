@extends('dashboard.app')
@section('title', 'Possible duplicate memberships — CBM Ondo')
@section('content')
<section class="panel">
    <h1>Possible duplicate memberships</h1>
    <p>These records share a name. Each needs review; no membership has been deleted or marked as fake.</p>
    <p><a href="{{ route('membership.index') }}">Back to memberships</a></p>
    @forelse($flags as $flag)
        <article class="panel">
            <h2>{{ $flag->candidate_name }}</h2>
            <p>Reference: {{ $flag->candidate_reference }} · Status: {{ $flag->status }}</p>
            <p>Activated profile: <a href="{{ route('membership.show', $flag->selected_id) }}">{{ $flag->selected_name }}</a></p>
            <p>{{ $flag->selection_skipped ? 'Member skipped profile selection.' : 'Member selected a profile.' }}</p>
            <a href="{{ route('membership.show', $flag->membership_id) }}">Inspect possible duplicate</a>
        </article>
    @empty
        <p>No possible duplicates have been flagged.</p>
    @endforelse
    {{ $flags->links() }}
</section>
@endsection
