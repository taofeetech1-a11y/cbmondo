<?php

namespace App\Http\Controllers;

use App\Models\CoordinatorAgent;
use App\Models\Membership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CoordinatorAgentController extends Controller
{
    public function store(Membership $membership): RedirectResponse
    {
        $password = config('coordinator.default_password');
        if (! is_string($password) || strlen($password) < 12) {
            throw ValidationException::withMessages(['coordinator' => 'Set COORDINATOR_DEFAULT_PASSWORD to at least 12 characters in the hosting configuration before creating accounts.']);
        }
        DB::transaction(function () use ($membership, $password): void {
            $member = Membership::whereKey($membership->id)->lockForUpdate()->firstOrFail();
            if ($member->coordinatorAgent()->exists()) {
                throw ValidationException::withMessages(['coordinator' => 'This member already has a coordinator-agent account.']);
            }
            $agent = new CoordinatorAgent;
            $agent->membership_id = $member->id;
            $agent->password = $password;
            $agent->save();
        });

        return redirect()->route('membership.show', $membership)->with('status', 'Coordinator-agent account created. Share their CBM ID, coordinator login link and the configured default password privately.');
    }
}
