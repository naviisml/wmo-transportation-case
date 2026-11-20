<?php

namespace Database\Seeders\Demo;

use App\Actions\RenewBudgetPeriod;
use App\Models\Resident;
use App\Models\WmoAuthorization;
use Carbon\CarbonImmutable;

class WmoAuthorizationSeeder extends DemoDataSeeder
{
    private const ANNUAL_METERS = 1_500_000;

    protected function seed(): void
    {
        $today = CarbonImmutable::today('Europe/Amsterdam');

        foreach (ResidentSeeder::references() as $number => $reference) {
            if ($number % 8 === 0) {
                continue;
            }

            $resident = Resident::where('reference', $reference)
                ->firstOrFail();

            $authorization = WmoAuthorization::firstOrCreate(
                ['resident_id' => $resident->id],
                [
                    'starts_on' => $today->subYearsNoOverflow(2)->startOfYear(),
                    'annual_meters' => $number % 5 === 0 || $number % 6 === 0 ? 12_000 : self::ANNUAL_METERS,
                ],
            );

            app(RenewBudgetPeriod::class)->handle($authorization->id);

            if ($authorization->wasRecentlyCreated && $number % 7 === 0) {
                $authorization->update([
                    'terminated_on' => $today->subDay(),
                ]);
            }
        }
    }
}
