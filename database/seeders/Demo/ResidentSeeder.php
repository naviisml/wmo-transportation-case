<?php

namespace Database\Seeders\Demo;

use App\Models\Address;
use App\Models\Parcel;
use App\Models\Resident;
use Generator;

class ResidentSeeder extends DemoDataSeeder
{
    private const COUNT = 24;

    protected function seed(): void
    {
        foreach (self::references() as $number => $reference) {
            $parcel = Parcel::where(
                'name',
                $number % 2 === 0 ? 'Utrecht Noord' : 'Utrecht Zuid',
            )->firstOrFail();

            $resident = Resident::firstOrCreate(
                ['reference' => $reference],
                Resident::factory()->raw([
                    'reference' => $reference,
                    'municipality' => Resident::MUNICIPALITY_UTRECHT,
                ]),
            );

            Address::firstOrCreate(
                ['resident_id' => $resident->id],
                Address::factory()->raw([
                    'resident_id' => $resident->id,
                    'parcel_id' => $parcel->id,
                    'house_number' => (string) $number,
                ]),
            );
        }
    }

    public static function references(): Generator
    {
        for ($number = 1; $number <= self::COUNT; $number++) {
            yield $number => sprintf('DEMO-%03d', $number);
        }
    }
}
