<?php

namespace App\Support;

use App\Models\ExecutiveAssignment;
use App\Models\ExecutivePosition;
use App\Models\Lga;
use App\Models\Membership;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExecutiveAppointments
{
    public static function matches(ExecutivePosition $position, Membership $member): bool
    {
        if ($position->level === 'lga') {
            return Lga::whereKey($member->lga)->exists();
        }

        return ($position->level === 'state' || (string) $position->lga_id === (string) $member->lga)
            && (! $position->ward_id || (string) $position->ward_id === (string) $member->ward)
            && (! $position->polling_unit_id || (string) $position->polling_unit_id === (string) $member->pu);
    }

    /** @return Collection<int, ExecutivePosition> */
    public static function positionsFor(Membership $member): Collection
    {
        return ExecutivePosition::with(['assignments' => fn (HasMany $query) => $query->whereIn('scope_key', [0, (int) $member->lga]), 'lga', 'ward', 'pollingUnit'])
            ->where(function (Builder $query) use ($member): void {
                $query->whereIn('level', ['state', 'lga'])->orWhere(function (Builder $query) use ($member): void {
                    $query->where('lga_id', $member->lga)
                        ->where(fn (Builder $query) => $query->whereNull('ward_id')->orWhere('ward_id', $member->ward))
                        ->where(fn (Builder $query) => $query->whereNull('polling_unit_id')->orWhere('polling_unit_id', $member->pu));
                });
            })->orderBy('level')->orderBy('name')->get();
    }

    public static function assign(int $memberId, int $positionId): void
    {
        DB::transaction(function () use ($memberId, $positionId): void {
            $member = Membership::whereKey($memberId)->lockForUpdate()->firstOrFail();
            $position = ExecutivePosition::whereKey($positionId)->lockForUpdate()->firstOrFail();
            if (! self::matches($position, $member)) {
                throw ValidationException::withMessages(['executive_position_id' => 'This position is outside the member’s registered location. Search for the member again.']);
            }
            $scopeKey = $position->level === 'lga' ? (int) $member->lga : 0;
            if ($position->assignments()->where('scope_key', $scopeKey)->exists()) {
                throw ValidationException::withMessages(['executive_position_id' => 'This position is already occupied. Choose a vacant position.']);
            }
            $group = $position->level === 'state' ? 'state' : 'local';
            if ($member->executiveAssignments()->where('assignment_group', $group)->exists()) {
                throw ValidationException::withMessages(['executive_position_id' => 'This member already holds a '.$group.' position. Remove that assignment before assigning another.']);
            }
            ExecutiveAssignment::create(['membership_id' => $member->id, 'executive_position_id' => $position->id, 'assignment_group' => $group, 'scope_key' => $scopeKey]);
        }, 3);
    }

    public static function assertLocationChange(Membership $member): void
    {
        if (! $member->isDirty(['lga', 'ward', 'pu'])) {
            return;
        }
        foreach ($member->executiveAssignments()->with('position')->get() as $assignment) {
            if (! self::matches($assignment->position, $member) || ($assignment->position->level === 'lga' && (int) $assignment->scope_key !== (int) $member->lga)) {
                throw ValidationException::withMessages(['lga' => 'Remove this member’s local executive assignment before changing the location they represent.']);
            }
        }
    }
}
