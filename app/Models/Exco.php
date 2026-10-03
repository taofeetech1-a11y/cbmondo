<?php

namespace App\Models;

use Database\Factories\ExcoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Exco extends Model
{
    /** @use HasFactory<ExcoFactory> */
    use HasFactory;

    protected $primaryKey = 'membership_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['membership_id'];

    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }
}
