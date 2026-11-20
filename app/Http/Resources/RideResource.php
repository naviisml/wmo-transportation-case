<?php

namespace App\Http\Resources;

use App\Enums\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RideResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'resident' => ['id' => $this->resident->id, 'name' => $this->resident->name],
            'origin' => $this->origin,
            'destination' => $this->destination,
            'scheduled_at' => $this->scheduled_at->toIso8601String(),
            'distance_meters' => $this->distance_meters,
            'status' => $this->status->value,
            'taxi_company' => ['id' => $this->taxiCompany->id, 'name' => $this->taxiCompany->name],
            'budget' => $this->when(
                $request->user()->role === Role::Callcenter,
                fn () => new BudgetPeriodResource($this->budgetPeriod),
            ),
        ];
    }
}
