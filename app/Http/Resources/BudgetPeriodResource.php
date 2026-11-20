<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BudgetPeriodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'starts_on' => $this->starts_on->toDateString(),
            'ends_on' => $this->ends_on->toDateString(),
            'allocated_meters' => $this->allocated_meters,
            'consumed_meters' => $this->consumed_meters,
            'remaining_meters' => $this->allocated_meters - $this->consumed_meters,
        ];
    }
}
