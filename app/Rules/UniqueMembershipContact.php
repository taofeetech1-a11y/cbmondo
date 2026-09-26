<?php

namespace App\Rules;

use App\Models\Membership;
use App\Support\MembershipContact;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueMembershipContact implements ValidationRule
{
    public function __construct(private ?Membership $member = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $query = Membership::query();
        if ($this->member !== null) {
            $query->whereKeyNot($this->member->getKey());
        }
        if ($attribute === 'phone') {
            $query->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(TRIM(phone), ' ', ''), '-', ''), '(', ''), ')', '') IN (?, ?, ?)", MembershipContact::phoneVariants($value));
        } else {
            $query->whereRaw('LOWER(TRIM(email)) = ?', [mb_strtolower(trim($value))]);
        }

        if ($query->exists()) {
            $fail($attribute === 'phone' ? 'This phone number is already registered, possibly in another format.' : 'This email address is already registered.');
        }
    }
}
