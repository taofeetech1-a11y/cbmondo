<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateVoterRequest;
use App\Models\Voter;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class VoterController extends Controller
{
    public function edit(Voter $voter): View
    {
        return view('voters.edit', compact('voter'));
    }

    public function update(UpdateVoterRequest $request, Voter $voter): RedirectResponse
    {
        $voter->update($request->validated());

        return redirect()->route('voters.edit', $voter)->with('status', 'Voter details updated.');
    }
}
