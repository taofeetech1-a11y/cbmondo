<?php

namespace App\Http\Controllers;

use App\Models\CoordinatorAgent;
use App\Models\Membership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CoordinatorAgentController extends Controller
{
    public function store(Membership $membership): RedirectResponse
    {
        $password = Str::password(24);
        DB::transaction(function () use ($membership, $password): void {
            $member = Membership::whereKey($membership->id)->lockForUpdate()->firstOrFail();
            if ($member->coordinatorAgent()->exists()) {
                throw ValidationException::withMessages(['coordinator' => 'This member already has a coordinator-agent account.']);
            }
            $agent = new CoordinatorAgent;
            $agent->membership_id = $member->id;
            $agent->password = $password;
            $agent->temporary_password_expires_at = now()->addDay();
            $agent->save();
        });

        return redirect()->route('membership.show', $membership)
            ->with('status', 'Coordinator-agent account created. Share these temporary credentials privately.')
            ->with('coordinator_credentials', ['member_id' => $membership->id, 'password' => $password]);
    }

    public function update(Request $request, Membership $membership): RedirectResponse
    {
        $data = $request->validate(['action' => ['required', Rule::in(['suspend', 'activate', 'reset-password'])]]);
        $password = $data['action'] === 'reset-password' ? Str::password(24) : null;
        DB::transaction(function () use ($membership, $data, $password): void {
            $agent = CoordinatorAgent::where('membership_id', $membership->id)->lockForUpdate()->firstOrFail();
            $agent->session_version++;
            $agent->remember_token = null;
            if ($password !== null) {
                $agent->password = $password;
                $agent->password_changed_at = null;
                $agent->temporary_password_expires_at = now()->addDay();
            } else {
                $agent->is_active = $data['action'] === 'activate';
            }
            $agent->save();
        });

        $response = redirect()->route('membership.show', $membership)->with('status', 'Coordinator access updated. Existing sessions have been revoked.');
        if ($password !== null) {
            $response->with('coordinator_credentials', ['member_id' => $membership->id, 'password' => $password]);
        }

        return $response;
    }
}
