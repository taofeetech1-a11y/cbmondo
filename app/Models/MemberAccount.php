<?php

namespace App\Models;

use Database\Factories\MemberAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

class MemberAccount extends Authenticatable
{
    /** @use HasFactory<MemberAccountFactory> */
    use HasFactory;

    protected $guarded = ['id', 'membership_id', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'is_active' => 'boolean'];
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }
}
