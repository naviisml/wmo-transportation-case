<?php

namespace App\Console\Commands;

use App\Actions\RenewBudgetPeriod;
use App\Models\WmoAuthorization;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class RenewBudgets extends Command
{
    protected $signature = 'wmo:renew-budgets';

    protected $description = 'Open missing annual budgets for active Wmo authorizations';

    public function handle(RenewBudgetPeriod $action): int
    {
        $today = CarbonImmutable::today('Europe/Amsterdam');

        WmoAuthorization::query()
            ->where('starts_on', '<=', $today->toDateString())
            ->where(fn ($query) => $query
                ->whereNull('terminated_on')
                ->orWhereColumn('terminated_on', '>', 'starts_on'))
            ->eachById(fn (WmoAuthorization $authorization) => $action->handle($authorization->id, $today));

        $this->info('Budget periods reconciled.');

        return self::SUCCESS;
    }
}
