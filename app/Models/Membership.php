<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Membership extends Model
{
    protected $guarded = [];


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
        static::creating(function (Membership $guest) {
            do {
                $passId = 'CBM-ON-' . Str::lower(Str::random(6));
            } while (static::where('cbm_id', $passId)->exists());

            $guest->cbm_id = $passId;
        });
    }
}
