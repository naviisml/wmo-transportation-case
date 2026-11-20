<?php

use App\Models\BudgetPeriod;
use App\Models\Parcel;
use App\Models\Resident;
use App\Models\TaxiCompany;
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
