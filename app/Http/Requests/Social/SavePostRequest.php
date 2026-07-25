<?php

namespace App\Http\Requests\Social;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SavePostRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'media_ids' => $this->input('media_ids', []),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'social_account_id' => ['required', 'integer', 'exists:social_accounts,id'],
            'caption' => ['nullable', 'string', 'max:2200'],
            'platform' => ['required', 'string', 'in:facebook,instagram'],
            'visibility' => ['required', 'string', 'in:public,friends,only_me'],
            'status' => ['required', 'string', 'in:draft,scheduled'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'media_ids' => ['nullable', 'array'],
            'media_ids.*' => ['integer', 'exists:social_media,id'],
        ];
    }

    /**
     * Get custom messages for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'social_account_id.exists' => 'The selected social account does not exist.',
            'scheduled_at.after' => 'The scheduled time must be in the future.',
        ];
    }
}
