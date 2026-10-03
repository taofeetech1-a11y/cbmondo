@extends('dashboard.app')
@section('title', 'Excos — CBM Ondo')
@section('content')
    <div class="page-head">
        <div><span class="eyebrow">CITY BOY MOVEMENT · ONDO STATE</span>
            <h1>Excos Dashboard</h1>
            <p>Manage your executive members across Ondo State.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('excos.create') }}">+ Add Exco</a>
    </div>
    @if (session('status'))
        <div class="member-notice" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="member-notice member-error" role="alert">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif
    <div class="stats exco-stats">
        @foreach (['total' => ['Total Excos', 'stat-total'], 'male' => ['Male Excos', 'stat-green'], 'female' => ['Female Excos', 'stat-violet']] as $key => [$label, $color])
            <section class="stat-card {{ $color }}">
                <div class="stat-heading">{{ $label }}</div>
                <div class="stat-val">{{ number_format($stats[$key]) }}</div>
                <p class="stat-sub">Matching current filters</p>
            </section>
        @endforeach
    </div>
    <form class="filters exco-filters" method="GET" action="{{ route('excos.index') }}">
        <div class="field"><label for="exco-search">Name or ID</label><input id="exco-search" name="q"
                maxlength="100" value="{{ request('q') }}" placeholder="Search Excos"></div>
        <div class="field"><label for="exco-lga">LGA</label><select id="exco-lga" name="lga"
                onchange="this.form.elements.ward.value='';this.form.elements.pu.value='';this.form.submit()">
                <option value="">All LGAs</option>
                @foreach ($lgas as $lga)
                    <option value="{{ $lga->id }}" @selected(request('lga') == $lga->id)>{{ $lga->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="field"><label for="exco-ward">Ward</label><select id="exco-ward" name="ward"
                onchange="this.form.elements.pu.value='';this.form.submit()" @disabled($wards->isEmpty())>
                <option value="">All wards</option>
                @foreach ($wards as $ward)
                    <option value="{{ $ward->id }}" @selected(request('ward') == $ward->id)>{{ $ward->code }}
                        {{ $ward->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="field"><label for="exco-pu">Polling unit</label><select id="exco-pu" name="pu"
                @disabled($pollingUnits->isEmpty())>
                <option value="">All polling units</option>
                @foreach ($pollingUnits as $unit)
                    <option value="{{ $unit->id }}" @selected(request('pu') == $unit->id)>{{ $unit->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="field"><label for="exco-gender">Gender</label><select id="exco-gender" name="gender">
                <option value="">All genders</option>
                <option value="male" @selected(request('gender') === 'male')>Male</option>
                <option value="female" @selected(request('gender') === 'female')>Female</option>
            </select></div>
        <button class="btn btn-primary" type="submit">Apply filters</button><a class="btn btn-outline"
            href="{{ route('excos.index') }}">Clear filters</a>
    </form>
    <section class="table-panel">
        <div class="table-head">
            <div>
                <h2>Excos directory</h2>
                <p>{{ number_format($members->total()) }} matching Excos. Profile changes also update membership records.
                </p>
            </div>
        </div>
        <div class="table-scroll" tabindex="0" role="region" aria-label="Excos list">
            <table>
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">CBM ID</th>
                        <th scope="col">Member ID</th>
                        <th scope="col">Gender</th>
                        <th scope="col">LGA</th>
                        <th scope="col">Ward</th>
                        <th scope="col">Polling unit</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($members as $member)
                        <tr>
                            <td>{{ $member->name }}</td>
                            <td>{{ $member->cbm_id }}</td>
                            <td>{{ $member->cbm_delimitation_code ?: 'Not assigned' }}</td>
                            <td>{{ ucfirst($member->gender) }}</td>
                            <td>{{ $member->lgaInfo?->name ?? '—' }}</td>
                            <td>{{ $member->wardInfo?->name ?? '—' }}</td>
                            <td>{{ $member->puInfo?->name ?? '—' }}</td>
                            <td>
                                <div class="exco-actions">
                                    <a class="btn btn-outline"
                                        href="{{ route('membership.show', ['membership' => $member, 'from' => 'excos', 'filters' => $filters]) }}">View</a>
                                    <a class="btn btn-outline"
                                        href="{{ route('membership.edit', ['membership' => $member, 'from' => 'excos', 'filters' => $filters]) }}">Edit</a>
                                    <form method="POST" action="{{ route('excos.destroy', $member->id) }}"
                                        onsubmit="return confirm('Remove this member from Excos? Their membership will be kept.')">
                                        @csrf @method('DELETE')<button class="btn btn-outline" type="submit"
                                            aria-label="Remove {{ $member->name }} from Excos">Remove</button></form>
                                </div>
                            </td>
                        </tr>
                    @empty<tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <h3>No Excos found</h3>
                                    <p>Try different filters or use Add Exco to select an existing member.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="table-foot">{{ $members->links() }}</div>
    </section>
@endsection
@push('styles')
    <style>
        .exco-stats {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .exco-filters input {
            width: 100%;
            min-width: 0;
            padding: 12px;
            border: 1px solid #d9e4de;
            border-radius: 10px;
            font: inherit;
            background: #f8fbf9;
        }

        @media(max-width:600px) {
            .exco-stats {
                grid-template-columns: minmax(0, 1fr);
            }
        }

        .exco-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .exco-actions form {
            margin: 0;
        }
    </style>
@endpush
