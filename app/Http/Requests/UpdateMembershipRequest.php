<?php

namespace App\Http\Requests;

use App\Support\MembershipValidation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMembershipRequest extends FormRequest
{
    /**
     * Normalize contacts before applying validation and uniqueness checks.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(MembershipValidation::normalize($this->all()));
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return MembershipValidation::rules($this->all(), $this->route('membership'));
    }
}
