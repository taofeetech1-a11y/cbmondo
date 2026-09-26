<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PollingUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'ward_id',
        'pu_number',
        'name',
        'delimitation_code',
        'status',
        'next_member_number',
    ];

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    public function members(){
        return $this->hasMany(Members::class, 'polling_unit');
    }
}
