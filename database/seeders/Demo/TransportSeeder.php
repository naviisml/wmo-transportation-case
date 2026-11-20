<?php

namespace Database\Seeders\Demo;

use App\Models\Parcel;
use App\Models\ParcelAssignment;
use App\Models\TaxiCompany;

class TransportSeeder extends DemoDataSeeder
{
    public const ASSIGNMENT_START = '2020-01-01';

    private const ASSIGNMENTS = [
        'Utrecht Noord' => 'Demo Taxi A',
        'Utrecht Zuid' => 'Demo Taxi B',
    ];

    protected function seed(): void
    {
        foreach (self::ASSIGNMENTS as $parcelName => $companyName) {
            $company = TaxiCompany::firstOrCreate([
                'name' => $companyName,
            ]);

            $parcel = Parcel::firstOrCreate([
                'name' => $parcelName,
            ]);

            ParcelAssignment::firstOrCreate(
                [
                    'parcel_id' => $parcel->id,
                    'starts_on' => self::ASSIGNMENT_START,
                ],
                [
                    'taxi_company_id' => $company->id,
                ],
            );
        }
    }
}
