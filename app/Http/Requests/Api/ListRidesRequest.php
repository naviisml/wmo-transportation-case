<?php

namespace App\Http\Requests\Api;

use App\Enums\RideStatus;
use App\Models\Ride;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListRidesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Ride::class);
    }

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', Rule::when($this->filled('from'), 'after_or_equal:from')],
            'status' => ['nullable', Rule::enum(RideStatus::class)],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
