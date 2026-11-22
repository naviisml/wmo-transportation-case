<?php

namespace Database\Seeders\Demo;

use App\Enums\RideStatus;
use App\Models\Address;
use App\Models\ParcelAssignment;
use App\Models\Resident;
use App\Models\Ride;
use App\Models\User;
use Carbon\CarbonImmutable;

class RideSeeder extends DemoDataSeeder
{
    protected function seed(): void
    {
        $booker = User::where('email', 'callcenter@example.test')
            ->firstOrFail();

        foreach (ResidentSeeder::references() as $number => $reference) {
            if ($number % 8 === 0) {
                continue;
            }

            $resident = Resident::where('reference', $reference)
                ->firstOrFail();

            $this->createRide($resident, $booker, $number);
        }
    }

    private function createRide(
        Resident $resident,
        User $booker,
        int $number,
    ): void {
        $bookingKey = "demo-reference-{$number}";
        if ($resident->rides()->where('idempotency_key', $bookingKey)->exists()) {
            return;
        }

        $authorization = $resident->authorization()->firstOrFail();
        $termination = $authorization->terminated_on?->toDateString();

        $period = $authorization->budgetPeriods()
            ->when($termination, fn ($query) => $query->where('starts_on', '<', $termination))
            ->latest('starts_on')
            ->first();

        if (! $period || $period->rides()->exists()) {
            return;
        }

        $now = CarbonImmutable::now('Europe/Amsterdam');
        $end = min($period->ends_on->toDateString(), $termination ?? $period->ends_on->toDateString());
        $latest = CarbonImmutable::parse($end, $now->timezone)->subSecond();
        $earliest = CarbonImmutable::parse($period->starts_on->toDateString(), $now->timezone);
        $preferred = $number % 3 === 0 ? $now->subDay() : $now->addDays($number % 4 + 1);
        $scheduled = max($earliest, min($preferred, $latest));

        $address = Address::where('resident_id', $resident->id)
            ->firstOrFail();

        $assignment = ParcelAssignment::where('parcel_id', $address->parcel_id)
            ->applicableOn($scheduled)
            ->firstOrFail();

        $distance = min(12_000, $period->allocated_meters - $period->consumed_meters);
        if ($number % 5 === 0 && $number % 6 !== 0) {
            $distance = min($distance, $period->allocated_meters - $period->consumed_meters - 500);
        }
        if ($distance <= 0) {
            return;
        }

        Ride::create([
            'resident_id' => $resident->id,
            'budget_period_id' => $period->id,
            'wmo_authorization_id' => $resident->authorization->id,
            'parcel_id' => $assignment->parcel_id,
            'parcel_assignment_id' => $assignment->id,
            'taxi_company_id' => $assignment->taxi_company_id,
            'booked_by' => $booker->id,
            'origin' => "Voorbeeldstraat {$number}, Utrecht",
            'destination' => 'Demo ontmoetingscentrum, Utrecht',
            'scheduled_at' => $scheduled->utc(),
            'distance_meters' => $distance,
            'status' => $scheduled->lte($now) ? RideStatus::Completed : RideStatus::Booked,
            'idempotency_key' => $bookingKey,
            'request_hash' => hash('sha256', "demo-{$number}"),
        ]);

        $period->increment('consumed_meters', $distance);
    }
}
