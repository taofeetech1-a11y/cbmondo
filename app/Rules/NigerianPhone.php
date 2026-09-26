<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NigerianPhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/\A(?:(?:\+?234|0)[789][01][0-9]{8}|(?:\+?234|0)1[0-9]{7})\z/', $value)) {
            $fail('The :attribute must be a valid Nigerian number (e.g., 08031234567 or +2348031234567).');
        }
    }
}
