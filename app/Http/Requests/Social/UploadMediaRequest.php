<?php

namespace App\Http\Requests\Social;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UploadMediaRequest extends FormRequest
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
        return [
            'files' => ['required', 'array', 'max:10'],
            'files.*' => ['required', 'file', 'max:102400'],
            'files.*.mimes' => ['image/jpeg,image/png,image/gif,image/webp,video/mp4,video/mpeg,video/quicktime'],
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
            'files.required' => 'Please select at least one file to upload.',
            'files.max' => 'You can upload a maximum of 10 files at once.',
            'files.*.max' => 'Each file must be less than 100MB.',
            'files.*.mimes' => 'Only JPEG, PNG, GIF, WebP images and MP4, MPEG, QuickTime videos are allowed.',
        ];
    }
}
