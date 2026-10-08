<?php

namespace App\Models;

use Database\Factories\ExecutivePositionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExecutivePosition extends Model
{
    /** @use HasFactory<ExecutivePositionFactory> */
    use HasFactory;

    public const LEVELS = ['state' => 'State', 'lga' => 'LGA', 'ward' => 'Ward', 'polling_unit' => 'Polling unit'];

    protected $guarded = ['id'];

    public function assignments(): HasMany
    {
        return $this->hasMany(ExecutiveAssignment::class);
    }

    public function lga(): BelongsTo
    {
        return $this->belongsTo(Lga::class);
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    public function pollingUnit(): BelongsTo
    {
        return $this->belongsTo(PollingUnit::class);
    }

    public function locationLabel(): string
    {
        if ($this->level === 'lga') {
            return 'All LGAs';
        }

        return implode(' / ', array_filter([$this->lga?->name, $this->ward?->name, $this->pollingUnit?->name])) ?: 'Ondo State';
    }
}
