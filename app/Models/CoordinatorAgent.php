<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class CoordinatorAgent extends Authenticatable
{
    use HasFactory;

    protected $guarded = ['id', 'membership_id'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'password_changed_at' => 'datetime'];
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }

    public function phoneCollections(): HasMany
    {
        return $this->hasMany(Voter::class, 'phone_added_by');
    }
}
