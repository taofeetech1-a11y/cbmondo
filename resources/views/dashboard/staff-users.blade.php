@extends('dashboard.app')
@section('title', 'Staff accounts — CBM Ondo')
@section('content')
<div class="member-layout">
<div class="page-head"><div><span class="eyebrow">ACCESS MANAGEMENT</span><h1>Staff accounts</h1><p>Super admins have full access. Admins can view members, assign and remove appointments, and manage positions.</p></div></div>
@if(session('status'))<div class="member-notice" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="member-notice member-error" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<form class="panel member-form" method="POST" action="{{ route('staff-users.store') }}">@csrf<h2 class="member-full">Create staff account</h2>
@foreach(['name' => ['Full name','text'], 'email' => ['Email','email'], 'password' => ['Password','password'], 'password_confirmation' => ['Confirm password','password']] as $field => [$label,$type])<div class="field"><label for="staff-{{ $field }}">{{ $label }}</label><input id="staff-{{ $field }}" name="{{ $field }}" type="{{ $type }}" required @if($type === 'password') minlength="12" autocomplete="new-password" @else value="{{ old($field) }}" maxlength="255" @endif></div>@endforeach
<div class="field"><label for="staff-role">Role</label><select id="staff-role" name="role"><option value="admin">Admin</option><option value="super_admin">Super admin</option></select></div><div class="member-form-actions member-full"><button class="btn btn-primary" type="submit">Create account</button></div></form>
<section class="table-panel"><h2>Staff access</h2><p>Inactive accounts cannot sign in or access dashboard pages. Existing browser sessions lose access when the next request is made.</p><div class="table-scroll"><table><thead><tr><th>Name</th><th>Email</th><th>Access</th></tr></thead><tbody>@foreach($users as $user)<tr><td>{{ $user->name }}</td><td>{{ $user->email }}</td><td><form class="directory-settings" method="POST" action="{{ route('staff-users.update', $user) }}">@csrf @method('PATCH')<label>Role <select name="role" required>@if(! array_key_exists($user->role ?? '', \App\Models\User::ROLES))<option value="" selected disabled>Choose role</option>@endif
@foreach(\App\Models\User::ROLES as $role => $label)<option value="{{ $role }}" @selected($user->role === $role)>{{ $label }}</option>@endforeach</select></label><label>Status <select name="is_active"><option value="1" @selected($user->is_active)>Active</option><option value="0" @selected(! $user->is_active)>Inactive</option></select></label><button class="btn btn-outline" type="submit">Save access</button></form></td></tr>@endforeach</tbody></table></div><div class="table-foot">{{ $users->links() }}</div></section>
</div>
@endsection
