<?php

namespace App\Http\Requests\Api;

use App\Models\Resident;
use Illuminate\Foundation\Http\FormRequest;

class ListResidentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Resident::class);
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
