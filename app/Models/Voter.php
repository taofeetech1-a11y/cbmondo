<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Voter extends Model
{
    use HasFactory;

    protected $fillable = [
        'serial',
        'vin',
        'full_name',
        'surname',
        'other_names',
        'occupation',
        'phone',
        'spoken_to',
        'apc_member',
        'consent_given',
        'gender',
        'age',
        'photo_path',
        'state',
        'lga',
        'registration_area',
        'polling_unit',
        'polling_unit_id',
    ];

    public function pollingUnit(): BelongsTo
    {
        return $this->belongsTo(PollingUnit::class);
    }

    public function phoneAddedBy(): BelongsTo
    {
        return $this->belongsTo(CoordinatorAgent::class, 'phone_added_by');
    }

    protected function casts(): array
    {
        return ['phone_added_at' => 'datetime', 'spoken_to' => 'boolean', 'apc_member' => 'boolean', 'consent_given' => 'boolean'];
    }
}
