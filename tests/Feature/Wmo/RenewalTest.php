<?php

use App\Actions\RenewBudgetPeriod;
use App\Models\BudgetPeriod;
use App\Models\Resident;
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
    expect($a->budgetPeriods()->count())->toBe(0);
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
