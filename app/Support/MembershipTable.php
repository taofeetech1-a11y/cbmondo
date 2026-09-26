<?php

namespace App\Support;

use App\Models\Membership;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class MembershipTable
{
    public const SORTS = ['created_at' => 'Registered', 'name' => 'Name', 'cbm_id' => 'CBM ID', 'phone' => 'Phone', 'gender' => 'Gender', 'support_us' => 'Support us'];

    /** @param Builder<Membership> $query
     * @return Builder<Membership>
     */
    public static function ordered(Builder $query, Request $request): Builder
    {
        $request->validate([
            'sort' => ['nullable', 'in:'.implode(',', array_keys(self::SORTS))],
            'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
        ]);

        return $query->orderBy($request->input('sort') ?: 'created_at', $request->input('direction') ?: 'desc')->orderByDesc('id');
    }
}
