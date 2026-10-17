<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CoordinatorDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $agent = $request->user('coordinator');
        $agent->load('membership.lgaInfo', 'membership.wardInfo', 'membership.puInfo');

        return view('coordinator.dashboard', ['agent' => $agent, 'member' => $agent->membership]);
    }

    public function register(Request $request): RedirectResponse
    {
        return redirect()->route('coordinator.dashboard');
    }
}
