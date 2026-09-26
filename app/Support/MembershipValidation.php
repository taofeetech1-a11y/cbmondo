<?php

namespace App\Support;

use App\Models\Membership;
use App\Rules\NigerianPhone;
use App\Rules\UniqueMembershipContact;
use Illuminate\Validation\Rule;

class MembershipValidation
{
    /** @return array<string, mixed> */
    public static function normalize(array $data): array
    {
        if (is_string($data['phone'] ?? null)) {
            $data['phone'] = MembershipContact::phone($data['phone']);
        }
        if (is_string($data['email'] ?? null)) {
            $email = mb_strtolower(trim($data['email']));
            $data['email'] = $email === '' ? null : $email;
        }

        return $data;
    }

    /** @return array<string, array<mixed>> */
    public static function rules(array $data, ?Membership $member = null): array
    {
        return [
            'name' => ['required', 'string', 'max:30'],
            'phone' => ['bail', 'required', 'string', 'max:18', new NigerianPhone, new UniqueMembershipContact($member)],
            'email' => ['bail', 'nullable', 'email', 'max:50', new UniqueMembershipContact($member)],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'age_range' => ['required', Rule::in(['18-24', '25-34', '35-44', '45-54', '55-64', '65+'])],
            'has_voters_card' => ['required', Rule::in(['yes', 'no'])],
            'lga' => ['required', 'integer', Rule::exists('lgas', 'id')],
            'ward' => ['exclude_unless:has_voters_card,yes', 'required', 'integer', Rule::exists('wards', 'id')->where('lga_id', $data['lga'] ?? null)],
            'pu' => ['exclude_unless:has_voters_card,yes', 'required', 'integer', Rule::exists('polling_units', 'id')->where('ward_id', $data['ward'] ?? null)],
            'support_us' => ['required', Rule::in(['yes', 'no'])],
            'want_to_be_contacted' => ['required', Rule::in(['yes', 'no'])],
            'same_address' => ['exclude_unless:has_voters_card,yes', 'required', Rule::in(['yes', 'no'])],
        ];
    }
}
