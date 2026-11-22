<?php

namespace App\Actions;

use App\Exceptions\BookingConflict;
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

            if ($authorization->terminated_on !== null) {
                $termination = $authorization->terminated_on;
                $periods = BudgetPeriod::query()
                    ->where('wmo_authorization_id', $authorization->id)
                    ->lockForUpdate()
                    ->get();

                foreach ($periods as $period) {
                    if ($period->starts_on->gte($termination)
                        && ($period->consumed_meters > 0 || $period->rides()->exists())) {
                        throw new BookingConflict('Cannot remove budget periods that contain consumed meters or rides.');
                    }
                }

                foreach ($periods as $period) {
                    if ($period->starts_on->lt($termination)
                        && $period->ends_on->gt($termination)) {
                        $terminationStartUtc = $termination->startOfDay('Europe/Amsterdam')->utc();
                        if ($period->rides()->where('scheduled_at', '>=', $terminationStartUtc)->exists()) {
                            throw new BookingConflict('Cannot shorten a budget period that contains rides after the termination date.');
                        }

                        $period->update(['ends_on' => $termination->toDateString()]);
                    }
                }

                foreach ($periods as $period) {
                    if ($period->starts_on->gte($termination)
                        && $period->consumed_meters === 0
                        && ! $period->rides()->exists()) {
                        $period->delete();
                    }
                }
            }

            $startsOn = $authorization->starts_on;
            if ($startsOn->toDateString() > $through->toDateString()) {
                return;
            }

            $effectiveThrough = $through;
            if ($authorization->terminated_on !== null) {
                if ($authorization->terminated_on->toDateString() <= $startsOn->toDateString()) {
                    return;
                }

                if ($authorization->terminated_on->lt($effectiveThrough)) {
                    $effectiveThrough = $authorization->terminated_on;
                }
            }

            for ($year = $startsOn->year; $year <= $effectiveThrough->year; $year++) {
                $start = $this->renewalDate($startsOn, $year);
                if ($start->toDateString() > $effectiveThrough->toDateString()) {
                    break;
                }
                if ($authorization->terminated_on !== null
                    && $start->toDateString() >= $authorization->terminated_on->toDateString()) {
                    break;
                }

                $renewalEnd = $this->renewalDate($startsOn, $year + 1);
                $end = $renewalEnd;
                if ($authorization->terminated_on !== null
                    && $authorization->terminated_on->lt($end)) {
                    $end = $authorization->terminated_on;
                }

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
                if ($authorization->terminated_on !== null
                    && $period->starts_on->lt($authorization->terminated_on)
                    && $period->ends_on->gt($authorization->terminated_on)) {
                    $period->update(['ends_on' => $authorization->terminated_on->toDateString()]);
                }
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
