<?php

namespace App\Http\Requests\Api;

use App\Models\Ride;
use Illuminate\Foundation\Http\FormRequest;

class BookRideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Ride::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['idempotency_key' => $this->header('Idempotency-Key')]);
    }

    public function rules(): array
    {
        return [
            'resident_id' => ['required', 'integer', 'min:1'],
            'origin' => ['required', 'string', 'max:255'],
            'destination' => ['required', 'string', 'max:255'],
            'scheduled_at' => ['required', 'date', 'regex:/T.*(Z|[+-]\d{2}:\d{2})$/'],
            'distance_meters' => ['required', 'integer', 'min:1', 'max:1000000'],
            /**
             * @ignoreParam
             */
            'idempotency_key' => ['required', 'string', 'max:128', 'regex:/^[A-Za-z0-9._:-]+$/'],
            'taxi_company_id' => ['prohibited'],
            'parcel_id' => ['prohibited'],
            'status' => ['prohibited'],
            'booked_by' => ['prohibited'],
        ];
    }
}
