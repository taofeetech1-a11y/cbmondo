<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberProfileController extends Controller
{
    public function show(Request $request): View
    {
        $account = $request->user('member');
        abort_unless($account->is_active, 403);
        $membership = $account->membership()->with(['lgaInfo', 'wardInfo', 'puInfo'])->firstOrFail();

        return view('member.profile', compact('membership', 'account'));
    }
}
