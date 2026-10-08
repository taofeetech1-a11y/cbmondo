<?php

namespace App\Models;

use App\Support\ExecutiveAppointments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Membership extends Model
{
    protected $guarded = [];

    public function executiveAssignments(): HasMany
    {
        return $this->hasMany(ExecutiveAssignment::class);
    }

    public function exco(): HasOne
    {
        return $this->hasOne(Exco::class);
    }

    public function lgaInfo()
    {
        return $this->belongsTo(Lga::class, 'lga');
    }

    public function wardInfo()
    {
        return $this->belongsTo(Ward::class, 'ward');
    }

    public function puInfo()
    {
        return $this->belongsTo(PollingUnit::class, 'pu');
    }

    protected static function booted(): void
    {
        static::updating(fn (Membership $member) => ExecutiveAppointments::assertLocationChange($member));

        static::creating(function (Membership $guest) {
            do {
                $passId = 'CBM-ON-'.Str::lower(Str::random(6));
            } while (static::where('cbm_id', $passId)->exists());

            $guest->cbm_id = $passId;
        });
    }
}
