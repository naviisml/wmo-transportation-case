<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResidentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'reference' => $this->reference,
            'address' => $this->address ? [
                'street' => $this->address->street,
                'house_number' => $this->address->house_number,
                'postal_code' => $this->address->postal_code,
                'city' => $this->address->city,
            ] : null,
            'authorization' => $this->authorization ? [
                'starts_on' => $this->authorization->starts_on->toDateString(),
                'terminated_on' => $this->authorization->terminated_on?->toDateString(),
                'budget_periods' => BudgetPeriodResource::collection($this->authorization->budgetPeriods),
            ] : null,
        ];
    }
}
