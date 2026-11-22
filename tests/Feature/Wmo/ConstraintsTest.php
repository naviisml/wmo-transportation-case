<?php

use App\Models\Address;
use App\Models\BudgetPeriod;
use App\Models\Parcel;
use App\Models\ParcelAssignment;
use App\Models\Resident;
use App\Models\Ride;
use App\Models\TaxiCompany;
use App\Models\User;
use App\Models\WmoAuthorization;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
it('protects nonnegative balances at the database boundary', function () {
    $a = WmoAuthorization::create(['resident_id' => Resident::factory()->create()->id, 'starts_on' => '2026-01-01', 'annual_meters' => 100]);
    expect(fn () => BudgetPeriod::create(['wmo_authorization_id' => $a->id, 'starts_on' => '2026-01-01', 'ends_on' => '2027-01-01', 'allocated_meters' => 100, 'consumed_meters' => 101]))->toThrow(QueryException::class);
});
it('prevents overlapping periods in PostgreSQL', function () {
    $a = WmoAuthorization::create(['resident_id' => Resident::factory()->create()->id, 'starts_on' => '2026-01-01', 'annual_meters' => 100]);
    $a->budgetPeriods()->create(['starts_on' => '2026-01-01', 'ends_on' => '2027-01-01', 'allocated_meters' => 100]);
    expect(fn () => $a->budgetPeriods()->create(['starts_on' => '2026-06-01', 'ends_on' => '2027-06-01', 'allocated_meters' => 100]))->toThrow(QueryException::class);
});
it('prevents conflicting parcel assignments and permits adjacent assignments', function () {
    $p = Parcel::create(['name' => 'Demo']);
    $c = TaxiCompany::create(['name' => 'Demo']);
    $p->assignments()->create(['taxi_company_id' => $c->id, 'starts_on' => '2026-01-01', 'ends_on' => '2027-01-01']);
    $p->assignments()->create(['taxi_company_id' => $c->id, 'starts_on' => '2027-01-01', 'ends_on' => null]);
    expect(fn () => $p->assignments()->create(['taxi_company_id' => $c->id, 'starts_on' => '2026-06-01', 'ends_on' => null]))->toThrow(QueryException::class);
});
it('rejects a ride whose budget and authorization belong to different residents', function () {
    $residentA = Resident::factory()->create();
    $residentB = Resident::factory()->create();
    $authorizationA = WmoAuthorization::create(['resident_id' => $residentA->id, 'starts_on' => '2026-01-01', 'annual_meters' => 1000]);
    $authorizationB = WmoAuthorization::create(['resident_id' => $residentB->id, 'starts_on' => '2026-01-01', 'annual_meters' => 1000]);
    $periodB = BudgetPeriod::create(['wmo_authorization_id' => $authorizationB->id, 'starts_on' => '2026-01-01', 'ends_on' => '2027-01-01', 'allocated_meters' => 1000]);

    $parcel = Parcel::create(['name' => 'Budget mismatch parcel']);
    $company = TaxiCompany::create(['name' => 'Budget mismatch taxi']);
    $assignment = ParcelAssignment::create(['parcel_id' => $parcel->id, 'taxi_company_id' => $company->id, 'starts_on' => '2026-01-01']);
    Address::create(['resident_id' => $residentA->id, 'parcel_id' => $parcel->id, 'street' => 'Test', 'house_number' => '1', 'postal_code' => '3500 AA', 'city' => 'Utrecht']);
    $bookedBy = User::factory()->create();

    expect(fn () => Ride::create([
        'resident_id' => $residentA->id,
        'budget_period_id' => $periodB->id,
        'wmo_authorization_id' => $authorizationA->id,
        'parcel_id' => $parcel->id,
        'parcel_assignment_id' => $assignment->id,
        'taxi_company_id' => $company->id,
        'booked_by' => $bookedBy->id,
        'origin' => 'A', 'destination' => 'B', 'scheduled_at' => '2026-06-01 12:00:00+00',
        'distance_meters' => 100, 'idempotency_key' => 'budget-mismatch', 'request_hash' => str_repeat('a', 64),
    ]))->toThrow(QueryException::class);
});
it('rejects a ride whose resident and parcel address do not match', function () {
    $resident = Resident::factory()->create();
    $authorization = WmoAuthorization::create(['resident_id' => $resident->id, 'starts_on' => '2026-01-01', 'annual_meters' => 1000]);
    $period = BudgetPeriod::create(['wmo_authorization_id' => $authorization->id, 'starts_on' => '2026-01-01', 'ends_on' => '2027-01-01', 'allocated_meters' => 1000]);

    $parcelA = Parcel::create(['name' => 'Resident parcel A']);
    $parcelB = Parcel::create(['name' => 'Resident parcel B']);
    $company = TaxiCompany::create(['name' => 'Resident mismatch taxi']);
    Address::create(['resident_id' => $resident->id, 'parcel_id' => $parcelA->id, 'street' => 'Test', 'house_number' => '1', 'postal_code' => '3500 AA', 'city' => 'Utrecht']);
    $assignmentB = ParcelAssignment::create(['parcel_id' => $parcelB->id, 'taxi_company_id' => $company->id, 'starts_on' => '2026-01-01']);
    $bookedBy = User::factory()->create();

    expect(fn () => Ride::create([
        'resident_id' => $resident->id,
        'budget_period_id' => $period->id,
        'wmo_authorization_id' => $authorization->id,
        'parcel_id' => $parcelB->id,
        'parcel_assignment_id' => $assignmentB->id,
        'taxi_company_id' => $company->id,
        'booked_by' => $bookedBy->id,
        'origin' => 'A', 'destination' => 'B', 'scheduled_at' => '2026-06-01 12:00:00+00',
        'distance_meters' => 100, 'idempotency_key' => 'resident-mismatch', 'request_hash' => str_repeat('b', 64),
    ]))->toThrow(QueryException::class);
});
