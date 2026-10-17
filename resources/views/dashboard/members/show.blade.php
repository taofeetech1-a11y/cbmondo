@extends('dashboard.app')
@section('title', 'Member details — CBM Ondo')
@section('content')
<div class="member-layout">
    @if (session('status'))
        <div class="member-notice" role="status">{{ session('status') }}</div>
    @endif
    @error('coordinator')
        <div class="member-notice member-error" role="alert">{{ $message }}</div>
    @enderror
    @can('manage-users')
        <section class="panel" id="coordinator-access">
            <h2>Coordinator-agent access</h2>
            @if(session('coordinator_credentials.member_id') == $member->id && session('coordinator_credentials.password'))
                <div class="member-notice" role="status"><strong>Temporary password: {{ session('coordinator_credentials.password') }}</strong><p>Shown once. Expires in 24 hours. Share privately with this member; they must set their own password before accessing voters.</p></div>
            @endif
            @if ($member->coordinatorAgent)
                <p>This member has a coordinator-agent account. Sign in using CBM ID {{ $member->cbm_id }}.</p>
                <p>Status: <strong>{{ $member->coordinatorAgent->is_active ? 'Active' : 'Suspended' }}</strong></p>
                <form method="POST" action="{{ route('coordinator-agents.update', $member) }}">@csrf @method('PATCH')<input type="hidden" name="action" value="{{ $member->coordinatorAgent->is_active ? 'suspend' : 'activate' }}"><button class="btn btn-outline" type="submit">{{ $member->coordinatorAgent->is_active ? 'Suspend coordinator' : 'Activate coordinator' }}</button></form>
                <form method="POST" action="{{ route('coordinator-agents.update', $member) }}">@csrf @method('PATCH')<input type="hidden" name="action" value="reset-password"><button class="btn btn-outline" type="submit">Issue new temporary password</button></form>
            @else
                <p>Create an account with a unique temporary password, valid for 24 hours.</p>
                <form method="POST" action="{{ route('coordinator-agents.store', $member) }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Create coordinator-agent</button>
                </form>
            @endif
            <a href="{{ route('coordinator.login') }}">Coordinator-agent sign in</a>
        </section>
    @endcan
    <a class="btn btn-outline" href="{{ route($directoryRoute, $filters) }}">← Back to {{ $directoryRoute === 'excos.index' ? 'Excos' : 'members' }}</a>
    <div class="page-head">
        <div><span class="eyebrow">MEMBER PROFILE</span><h1>{{ $member->name }}</h1><p>{{ $member->cbm_id }}</p></div>
        @can('update-members')<a class="btn btn-primary" href="{{ route('membership.edit', ['membership' => $member, 'filters' => $filters, 'from' => $directoryRoute === 'excos.index' ? 'excos' : null]) }}">Update member</a>@endcan
    </div>
    @can('download-cards')<section class="panel member-id-section" aria-labelledby="member-id-title">
        <div class="panel-head"><div><span class="eyebrow">YOUR MEMBERSHIP</span><h2 id="member-id-title">CBM ID card</h2></div>
            <a class="btn btn-primary" href="{{ route('membership.card', ['membership' => $member, 'download' => 1]) }}" download>Download ID card</a>
        </div>
        <p class="member-help">PNG · 1200 × 704 pixels. Your downloaded card keeps the same size and layout on every device.</p>
        <img class="member-id-preview" src="{{ route('membership.card', $member) }}" width="1200" height="704" alt="City Boy Movement Ondo State membership card for {{ $member->name }}. CBM ID: {{ $member->cbm_id }}. Member ID: {{ $member->cbm_delimitation_code ?: 'Not assigned' }}.">
    </section>@endcan
    <section class="panel">
        <dl class="member-details">
            @foreach ([
                'Full name' => $member->name,
                'CBM ID' => $member->cbm_id,
                'Phone' => $member->phone,
                'Email' => $member->email,
                'Gender' => ucfirst($member->gender),
                'Age range' => $member->age_range,
                'State' => $member->state,
                'LGA' => $member->lgaInfo?->name,
                'Ward' => $member->wardInfo?->name,
                'Polling unit' => $member->puInfo?->name,
                'Voter card' => $member->ward !== null ? 'Yes' : 'No',
                'Delimitation code' => $member->delimitation_code,
                'CBM delimitation code' => $member->cbm_delimitation_code,
                'Supports the movement' => ucfirst($member->support_us),
                'Wants to be contacted' => ucfirst($member->want_to_be_contacted),
                'Lives at voter-card location' => $member->same_address !== null ? ucfirst($member->same_address) : 'Not applicable',
                'Registered' => $member->created_at->format('d M Y, H:i'),
                'Last updated' => $member->updated_at->format('d M Y, H:i'),
            ] as $label => $value)
                <div><dt>{{ $label }}</dt><dd>{{ $value ?: '—' }}</dd></div>
            @endforeach
        </dl>
    </section>
</div>
@endsection
