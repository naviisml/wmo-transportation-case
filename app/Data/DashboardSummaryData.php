<?php

namespace App\Data;

use App\Enums\Role;

readonly class DashboardSummaryData
{
    public function __construct(
        public Role $role,
        public int $ridesTotal,
        public int $upcomingRides,
        public ?int $residentsTotal = null,
        public ?int $activeAuthorizations = null,
        public ?BudgetTotalsData $budget = null,
        public ?int $terminatedAuthorizations = null,
        public ?int $withoutAuthorization = null,
        public ?int $taxiCompanies = null,
        public ?int $parcels = null,
        public ?int $uncoveredParcels = null,
    ) {}
}
