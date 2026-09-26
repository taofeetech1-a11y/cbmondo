<?php

namespace App\Http\Requests;

class RegisterMembershipRequest extends UpdateMembershipRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge(['has_voters_card' => $this->routeIs('cbm.register') ? 'yes' : 'no']);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = parent::rules();
        foreach (['age_range' => 'ageRange', 'pu' => 'polling_unit', 'support_us' => 'support', 'want_to_be_contacted' => 'contact_consent'] as $key => $formKey) {
            $rules[$formKey] = $rules[$key];
            unset($rules[$key]);
        }

        return $rules;
    }
}
