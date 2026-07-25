<?php

namespace App\Http\Requests\Social;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveCampaignRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'social_post_id' => ['nullable', 'integer', 'exists:social_posts,id'],
            'platform' => ['required', 'string', 'in:facebook,instagram'],
            'budget' => ['nullable', 'numeric', 'min:1', 'max:9999999.99'],
            'objective' => ['nullable', 'string', 'in:Engagement,Traffic,Conversions,Brand Awareness,Reach'],
            'start_date' => ['nullable', 'date', 'after:now'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'targeting' => ['nullable', 'array'],
            'targeting.age_min' => ['nullable', 'integer', 'min:13', 'max:65'],
            'targeting.age_max' => ['nullable', 'integer', 'min:13', 'max:65'],
            'targeting.geo_locations' => ['nullable', 'array'],
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
            'social_post_id.exists' => 'The selected post does not exist.',
            'start_date.after' => 'The start date must be in the future.',
            'end_date.after_or_equal' => 'The end date must be after or equal to the start date.',
        ];
    }
}
