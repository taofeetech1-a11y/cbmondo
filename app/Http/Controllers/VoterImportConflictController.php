<?php

namespace App\Http\Controllers;

use App\Models\VoterImportConflict;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VoterImportConflictController extends Controller
{
    public function index(): View
    {
        return view('voters.conflicts', [
            'conflicts' => VoterImportConflict::with('import.user:id,name', 'pollingUnit', 'existingPollingUnit', 'existingVoter')
                ->where('status', 'pending')->latest('id')->paginate(20),
        ]);
    }

    public function photo(VoterImportConflict $conflict): StreamedResponse
    {
        abort_unless($conflict->photo_path && str_starts_with($conflict->photo_path, 'voters/') && Storage::disk('local')->exists($conflict->photo_path), 404);

        return Storage::disk('local')->response($conflict->photo_path);
    }
}
