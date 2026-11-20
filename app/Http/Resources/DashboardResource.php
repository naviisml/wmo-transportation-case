<?php

namespace App\Http\Resources;

use App\Data\DashboardSummaryData;
use App\Enums\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read DashboardSummaryData $resource
 */
class DashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $summary = $this->resource;

        return [
            'role' => (string) $summary->role->value,
            'rides_total' => (int) $summary->ridesTotal,
            'upcoming_rides' => (int) $summary->upcomingRides,
            $this->mergeWhen($request->user()->role !== Role::TaxiCompany, fn () => [
                'residents_total' => (int) $summary->residentsTotal,
                'active_authorizations' => (int) $summary->activeAuthorizations,
                'budget' => new BudgetTotalsResource($summary->budget),
            ]),
            $this->mergeWhen($request->user()->role === Role::Municipality, fn () => [
                'terminated_authorizations' => (int) $summary->terminatedAuthorizations,
                'without_authorization' => (int) $summary->withoutAuthorization,
                'taxi_companies' => (int) $summary->taxiCompanies,
                'parcels' => (int) $summary->parcels,
                'uncovered_parcels' => (int) $summary->uncoveredParcels,
            ]),
        ];
    }
}
