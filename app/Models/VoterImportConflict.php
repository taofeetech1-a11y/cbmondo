<?php

namespace App\Models;

use Database\Factories\VoterImportConflictFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoterImportConflict extends Model
{
    /** @use HasFactory<VoterImportConflictFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['record' => 'array'];
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(VoterRegisterImport::class, 'voter_register_import_id');
    }

    public function pollingUnit(): BelongsTo
    {
        return $this->belongsTo(PollingUnit::class);
    }

    public function existingPollingUnit(): BelongsTo
    {
        return $this->belongsTo(PollingUnit::class, 'existing_polling_unit_id');
    }

    public function existingVoter(): BelongsTo
    {
        return $this->belongsTo(Voter::class, 'existing_voter_id');
    }
}
