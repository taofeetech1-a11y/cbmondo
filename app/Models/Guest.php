<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Guest extends Model
{
    protected $fillable = [
        'pass_id',
        'name',
        'phone',
        'email',
        'lga',
        'category',
        'allow_contact',
        'consent',
    ];



    protected static function booted(): void
    {
        static::creating(function (Guest $guest) {
            do {
                $passId = 'CBM-ONDO-' . Str::lower(Str::random(6));
            } while (static::where('pass_id', $passId)->exists());

            $guest->pass_id = $passId;
        });
    }




}


