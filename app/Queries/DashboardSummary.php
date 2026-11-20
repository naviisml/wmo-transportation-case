<?php

namespace App\Queries;

use App\Data\BudgetTotalsData;
use App\Data\DashboardSummaryData;
use App\Enums\RideStatus;
use App\Enums\Role;
use App\Models\BudgetPeriod;
use App\Models\Parcel;
use App\Models\Resident;
use App\Models\Ride;
use App\Models\TaxiCompany;
use App\Models\User;
use App\Models\WmoAuthorization;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class DashboardSummary
{
    public function forUser(User $user): DashboardSummaryData
    {
        $today = CarbonImmutable::today('Europe/Amsterdam');
        $rides = $this->ridesFor($user);
        $ridesTotal = (clone $rides)->count();
        $upcomingRides = (clone $rides)
            ->where('status', RideStatus::Booked)
            ->where('scheduled_at', '>=', now())
            ->count();

        if ($user->role === Role::TaxiCompany) {
            return new DashboardSummaryData($user->role, $ridesTotal, $upcomingRides);
        }

        $municipality = $user->role === Role::Municipality;

        return new DashboardSummaryData(
            role: $user->role,
            ridesTotal: $ridesTotal,
            upcomingRides: $upcomingRides,
            residentsTotal: Resident::query()->inUtrecht()->count(),
            activeAuthorizations: $this->utrechtAuthorizations()->activeOn($today)->count(),
            budget: $this->budgetTotals($today),
            terminatedAuthorizations: $municipality
                ? $this->utrechtAuthorizations()->where('terminated_on', '<=', $today->toDateString())->count()
                : null,
            withoutAuthorization: $municipality
                ? Resident::query()->inUtrecht()->whereDoesntHave('authorization')->count()
                : null,
            taxiCompanies: $municipality ? TaxiCompany::query()->count() : null,
            parcels: $municipality ? Parcel::query()->count() : null,
            uncoveredParcels: $municipality ? $this->uncoveredParcels($today) : null,
        );
    }

    private function ridesFor(User $user): Builder
    {
        return match ($user->role) {
            Role::TaxiCompany => Ride::query()->visibleTo($user),
            Role::Callcenter => Ride::query()->where('booked_by', $user->id),
            Role::Municipality => Ride::query(),
        };
    }

    private function budgetTotals(CarbonImmutable $today): BudgetTotalsData
    {
        $totals = BudgetPeriod::query()
            ->covering($today)
            ->whereHas('authorization', fn (Builder $query) => $query->activeOn($today))
            ->whereHas('authorization.resident', fn (Builder $query) => $query->inUtrecht())
            ->selectRaw('COALESCE(SUM(allocated_meters), 0) AS allocated')
            ->selectRaw('COALESCE(SUM(consumed_meters), 0) AS consumed')
            ->first();

        return new BudgetTotalsData(
            allocatedMeters: (int) $totals->allocated,
            consumedMeters: (int) $totals->consumed,
        );
    }

    private function utrechtAuthorizations(): Builder
    {
        return WmoAuthorization::query()->whereHas('resident', fn (Builder $query) => $query->inUtrecht());
    }

    private function uncoveredParcels(CarbonImmutable $today): int
    {
        return Parcel::query()
            ->whereDoesntHave('assignments', fn (Builder $query) => $query->applicableOn($today))
            ->count();
    }
}
