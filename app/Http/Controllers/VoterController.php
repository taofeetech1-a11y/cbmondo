<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateVoterRequest;
use App\Models\Voter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VoterController extends Controller
{
    public function photo(Voter $voter): StreamedResponse
    {
        abort_unless($voter->photo_path && str_starts_with($voter->photo_path, 'voters/') && Storage::disk('local')->exists($voter->photo_path), 404);

        return Storage::disk('local')->response($voter->photo_path);
    }

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
