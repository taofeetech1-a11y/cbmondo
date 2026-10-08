<?php

namespace App\Models;

use Database\Factories\ExecutiveAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExecutiveAssignment extends Model
{
    /** @use HasFactory<ExecutiveAssignmentFactory> */
    use HasFactory;

    protected $fillable = ['executive_position_id', 'membership_id', 'assignment_group', 'scope_key'];

    public function lga(): BelongsTo
    {
        return $this->belongsTo(Lga::class, 'scope_key');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(ExecutivePosition::class, 'executive_position_id');
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }
}
