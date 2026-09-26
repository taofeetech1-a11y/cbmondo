<?php

namespace App\Support;

class MembershipContact
{
    public static function phone(string $phone): string
    {
        $phone = str_replace([' ', '-', '(', ')'], '', trim($phone));
        if (str_starts_with($phone, '+234')) {
            return '0'.substr($phone, 4);
        }
        if (str_starts_with($phone, '234')) {
            return '0'.substr($phone, 3);
        }

        return $phone;
    }

    /** @return list<string> */
    public static function phoneVariants(string $phone): array
    {
        $phone = self::phone($phone);

        return [$phone, '234'.substr($phone, 1), '+234'.substr($phone, 1)];
    }
}
