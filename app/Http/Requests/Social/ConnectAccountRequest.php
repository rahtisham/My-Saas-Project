<?php

namespace App\Http\Requests\Social;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ConnectAccountRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'platform' => ['required', 'string', 'in:facebook,instagram'],
            'name' => ['required', 'string', 'max:255'],
            'page_id' => ['required', 'string', 'max:255'],
            'instagram_account_id' => ['nullable', 'string', 'max:255'],
            'access_token' => ['required', 'string', 'max:2048'],
        ];
    }
}
