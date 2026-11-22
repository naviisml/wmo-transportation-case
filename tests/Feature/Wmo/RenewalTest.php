<?php

use App\Actions\RenewBudgetPeriod;
use App\Exceptions\BookingConflict;
use App\Models\Address;
use App\Models\BudgetPeriod;
use App\Models\Parcel;
use App\Models\ParcelAssignment;
use App\Models\Resident;
use App\Models\Ride;
use App\Models\TaxiCompany;
use App\Models\User;
use App\Models\WmoAuthorization;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schedule;

uses(RefreshDatabase::class);
function authorizationAt(string $date)
{
    return WmoAuthorization::create(['resident_id' => Resident::factory()->create()->id, 'starts_on' => $date, 'annual_meters' => 100000]);
}
it('fills in missing budget periods and preserves consumption on reruns', function () {
    $a = authorizationAt('2020-04-10');
    $action = app(RenewBudgetPeriod::class);
    $action->handle($a->id, CarbonImmutable::parse('2024-04-09'));
    expect($a->budgetPeriods()->count())->toBe(4);
    $first = $a->budgetPeriods()->oldest('starts_on')->first();
    $first->update(['consumed_meters' => 12000]);
    $action->handle($a->id, CarbonImmutable::parse('2024-04-10'));
    $action->handle($a->id, CarbonImmutable::parse('2024-04-10'));
    expect($a->budgetPeriods()->count())->toBe(5);
    expect($first->fresh()->consumed_meters)->toBe(12000);
    expect($a->budgetPeriods()->latest('starts_on')->first()->consumed_meters)->toBe(0);
});
it('uses Feb 28 in non leap years and returns to Feb 29 in leap years', function () {
    $a = authorizationAt('2020-02-29');
    $action = app(RenewBudgetPeriod::class);
    $action->handle($a->id, CarbonImmutable::parse('2024-02-28'));
    expect($a->budgetPeriods()->count())->toBe(4);
    $action->handle($a->id, CarbonImmutable::parse('2024-02-29'));
    expect($a->budgetPeriods()->orderBy('starts_on')->get()->map(fn ($p) => [$p->starts_on->toDateString(), $p->ends_on->toDateString()])->all())->toBe([['2020-02-29', '2021-02-28'], ['2021-02-28', '2022-02-28'], ['2022-02-28', '2023-02-28'], ['2023-02-28', '2024-02-29'], ['2024-02-29', '2025-02-28']]);
});
it('does not renew terminated or not yet started authorizations', function () {
    $a = authorizationAt('2020-01-01');
    $a->update(['terminated_on' => '2021-01-01']);
    app(RenewBudgetPeriod::class)->handle($a->id);
    expect($a->budgetPeriods()->count())->toBe(1)
        ->and($a->budgetPeriods()->first()->starts_on->toDateString())->toBe('2020-01-01')
        ->and($a->budgetPeriods()->first()->ends_on->toDateString())->toBe('2021-01-01');

    $future = authorizationAt('2099-01-01');
    app(RenewBudgetPeriod::class)->handle($future->id);
    expect($future->budgetPeriods()->count())->toBe(0);
});
it('runs the reconciliation command repeatedly', function () {
    $a = authorizationAt(now()->subYearsNoOverflow(2)->toDateString());
    $this->artisan('wmo:renew-budgets')->assertSuccessful();
    $count = BudgetPeriod::count();
    $this->artisan('wmo:renew-budgets')->assertSuccessful();
    expect(BudgetPeriod::count())->toBe($count);
    expect($count)->toBe(3);
});

it('opens a new budget on the renewal date before termination', function () {
    $authorization = authorizationAt('2025-11-20');
    $authorization->update(['terminated_on' => '2027-11-20']);

    $this->travelTo(CarbonImmutable::parse('2026-11-20 00:00:00', 'Europe/Amsterdam'));
    $event = collect(Schedule::events())->first(fn ($event) => str_contains($event->command ?? '', 'wmo:renew-budgets'));
    expect($event)->not->toBeNull();
    expect($event->isDue($this->app))->toBeTrue();

    $this->artisan('wmo:renew-budgets')->assertSuccessful();
    $period = $authorization->budgetPeriods()->covering(CarbonImmutable::now())->firstOrFail();
    expect($period->allocated_meters)->toBe(100000)
        ->and($period->consumed_meters)->toBe(0);

    $this->travelTo(CarbonImmutable::parse('2027-11-20 00:00:00', 'Europe/Amsterdam'));
    $this->artisan('wmo:renew-budgets')->assertSuccessful();
    expect($authorization->budgetPeriods()->count())->toBe(2);
});
it('caps periods at a later termination date and never opens a period on or after termination', function () {
    $authorization = authorizationAt('2022-04-10');
    $authorization->update(['terminated_on' => '2025-04-10']);

    app(RenewBudgetPeriod::class)->handle(
        $authorization->id,
        CarbonImmutable::parse('2027-04-09'),
    );

    $periods = $authorization->budgetPeriods()->orderBy('starts_on')->get();

    expect($periods)->toHaveCount(3)
        ->and($periods->pluck('starts_on')->map->toDateString()->all())->toBe([
            '2022-04-10',
            '2023-04-10',
            '2024-04-10',
        ])
        ->and($periods->last()->ends_on->toDateString())->toBe('2025-04-10')
        ->and($periods->every(fn ($period) => $period->starts_on->lt($authorization->terminated_on)))->toBeTrue()
        ->and($periods->every(fn ($period) => $period->ends_on->lte($authorization->terminated_on)))->toBeTrue();
});

it('reconciles historical missed periods for an authorization that later terminated', function () {
    $authorization = authorizationAt('2020-04-10');
    $authorization->update(['terminated_on' => '2023-06-15']);

    $this->travelTo(CarbonImmutable::parse('2026-04-10 00:00:00', 'Europe/Amsterdam'));
    $this->artisan('wmo:renew-budgets')->assertSuccessful();

    $periods = $authorization->budgetPeriods()->orderBy('starts_on')->get();

    expect($periods)->toHaveCount(4)
        ->and($periods->pluck('starts_on')->map->toDateString()->all())->toBe([
            '2020-04-10',
            '2021-04-10',
            '2022-04-10',
            '2023-04-10',
        ])
        ->and($periods->last()->ends_on->toDateString())->toBe('2023-06-15');
});
it('removes post-termination periods and rescopes periods crossing a retroactive termination', function () {
    $authorization = authorizationAt('2022-04-10');

    $crossing = BudgetPeriod::create([
        'wmo_authorization_id' => $authorization->id,
        'starts_on' => '2023-04-10',
        'ends_on' => '2024-04-10',
        'allocated_meters' => 100000,
        'consumed_meters' => 0,
    ]);
    $later = BudgetPeriod::create([
        'wmo_authorization_id' => $authorization->id,
        'starts_on' => '2024-04-10',
        'ends_on' => '2025-04-10',
        'allocated_meters' => 100000,
        'consumed_meters' => 0,
    ]);

    $authorization->update(['terminated_on' => '2023-06-15']);
    app(RenewBudgetPeriod::class)->handle(
        $authorization->id,
        CarbonImmutable::parse('2026-04-10'),
    );

    expect($crossing->fresh()->ends_on->toDateString())->toBe('2023-06-15')
        ->and(BudgetPeriod::find($later->id))->toBeNull()
        ->and($authorization->budgetPeriods()->where('starts_on', '>=', '2023-06-15')->exists())->toBeFalse();
});
it('rejects shortening a crossing period that contains rides on or after retroactive termination', function () {
    $authorization = authorizationAt('2022-04-10');
    $crossing = BudgetPeriod::create([
        'wmo_authorization_id' => $authorization->id,
        'starts_on' => '2023-04-10',
        'ends_on' => '2024-04-10',
        'allocated_meters' => 100000,
        'consumed_meters' => 0,
    ]);

    $resident = $authorization->resident;
    $parcel = Parcel::create(['name' => 'Termination guard parcel']);
    $company = TaxiCompany::create(['name' => 'Termination guard taxi']);
    $assignment = ParcelAssignment::create([
        'parcel_id' => $parcel->id,
        'taxi_company_id' => $company->id,
        'starts_on' => '2022-01-01',
    ]);
    Address::create([
        'resident_id' => $resident->id,
        'parcel_id' => $parcel->id,
        'street' => 'Test',
        'house_number' => '1',
        'postal_code' => '3500 AA',
        'city' => 'Utrecht',
    ]);

    Ride::create([
        'resident_id' => $resident->id,
        'budget_period_id' => $crossing->id,
        'wmo_authorization_id' => $authorization->id,
        'parcel_id' => $parcel->id,
        'parcel_assignment_id' => $assignment->id,
        'taxi_company_id' => $company->id,
        'booked_by' => User::factory()->create()->id,
        'origin' => 'A',
        'destination' => 'B',
        'scheduled_at' => '2023-06-15 12:00:00+02:00',
        'distance_meters' => 100,
        'idempotency_key' => 'termination-guard',
        'request_hash' => str_repeat('c', 64),
    ]);

    $authorization->update(['terminated_on' => '2023-06-15']);

    expect(fn () => app(RenewBudgetPeriod::class)->handle(
        $authorization->id,
        CarbonImmutable::parse('2026-04-10'),
    ))->toThrow(BookingConflict::class, 'Cannot shorten a budget period that contains rides after the termination date.')
        ->and($crossing->fresh()->ends_on->toDateString())->toBe('2024-04-10');
});
