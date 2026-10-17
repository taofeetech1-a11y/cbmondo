<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateVoterRequest;
use App\Models\CoordinatorAgent;
use App\Models\Voter;
use Illuminate\Database\Eloquent\Builder;
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
        if ($request->user() instanceof CoordinatorAgent) {
            $updated = Voter::whereKey($voter->id)
                ->where('phone_added_by', $voter->phone_added_by)
                ->where(fn (Builder $query) => $query->whereNull('phone')->orWhere('phone', ''))
                ->update([
                    ...$request->validated(),
                    'phone_added_by' => $voter->phone_added_at ? $voter->phone_added_by : $request->user()->id,
                    'phone_added_at' => $voter->phone_added_at ?? now(),
                ]);
            abort_unless($updated === 1, 404);

            return redirect()->route('coordinator.dashboard')->with('status', 'Phone number added. This voter is now complete.');
        }

        $voter->update($request->validated());

        return redirect()->route('voters.edit', $voter)->with('status', 'Voter details updated.');
    }
}
