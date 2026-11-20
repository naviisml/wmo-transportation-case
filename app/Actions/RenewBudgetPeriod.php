<?php

namespace App\Actions;

use App\Models\BudgetPeriod;
use App\Models\WmoAuthorization;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class RenewBudgetPeriod
{
    /**
     * Add any missing annual budgets up to and including the given date.
     */
    public function handle(int $authorizationId, ?CarbonImmutable $through = null): void
    {
        $through ??= CarbonImmutable::today('Europe/Amsterdam');
        DB::transaction(function () use ($authorizationId, $through) {
            $authorization = WmoAuthorization::query()
                ->lockForUpdate()
                ->findOrFail($authorizationId);

            if (! $authorization->isActiveOn($through)) {
                return;
            }
            $startsOn = $authorization->starts_on;
            for ($year = $startsOn->year; $year <= $through->year; $year++) {
                $start = $this->renewalDate($startsOn, $year);
                if ($start->toDateString() > $through->toDateString()) {
                    break;
                }
                $end = $this->renewalDate($startsOn, $year + 1);
                $period = BudgetPeriod::query()->firstOrCreate(
                    [
                        'wmo_authorization_id' => $authorization->id,
                        'starts_on' => $start->toDateString(),
                    ],
                    [
                        'ends_on' => $end->toDateString(),
                        'allocated_meters' => $authorization->annual_meters,
                        'consumed_meters' => 0,
                    ],
                );
                if ($period->wasRecentlyCreated) {
                    DB::table('audit_entries')->insert([
                        'action' => 'budget.renewed',
                        'resource_type' => 'budget_period',
                        'resource_id' => $period->id,
                    ]);
                }
            }
        }, 3);
    }

    /**
     * Use February 28 in non-leap years for authorizations starting on February 29.
     */
    private function renewalDate(CarbonImmutable $startsOn, int $year): CarbonImmutable
    {
        $month = $startsOn->setDate($year, $startsOn->month, 1);

        return $month->day(min($startsOn->day, $month->daysInMonth));
    }
}
