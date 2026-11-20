<?php

namespace App\Http\Resources;

use App\Data\BudgetTotalsData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read BudgetTotalsData $resource
 */
class BudgetTotalsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'allocated_meters' => (int) $this->resource->allocatedMeters,
            'consumed_meters' => (int) $this->resource->consumedMeters,
            'remaining_meters' => (int) $this->resource->remainingMeters,
        ];
    }
}
