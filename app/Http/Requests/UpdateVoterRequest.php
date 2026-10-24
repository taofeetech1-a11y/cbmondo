<?php

namespace App\Http\Requests;

use App\Models\CoordinatorAgent;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVoterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
        $rules = [
            'phone' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9][0-9\s().-]{5,28}[0-9]$/'],
        ];
        if ($this->user() instanceof CoordinatorAgent) {
            foreach (['spoken_to', 'apc_member', 'consent_given'] as $field) {
                $rules[$field] = ['sometimes', 'boolean'];
            }
        }

        return $rules;
    }
}
