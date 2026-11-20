<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Laravel\Sanctum\TransientToken;

class CreateTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->currentAccessToken() instanceof TransientToken;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'password' => ['required', 'current_password:web'],
            'abilities' => ['prohibited'],
            'role' => ['prohibited'],
            'taxi_company_id' => ['prohibited'],
        ];
    }
}
