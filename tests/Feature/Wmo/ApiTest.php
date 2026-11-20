<?php

use App\Models\BudgetPeriod;
use App\Models\Resident;
use App\Models\Ride;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-20 12:00:00', 'Europe/Amsterdam'));
    config(['wmo.demo_password' => 'demo-assessment-only']);
    $this->seed(DemoSeeder::class);
    $this->callcenter = User::where('email', 'callcenter@example.test')->firstOrFail();
    $this->resident = Resident::where('reference', 'DEMO-001')->firstOrFail();
});

it('keeps demo data repeatable and prevents production seeding', function () {
    $counts = [Resident::count(), Ride::count(), BudgetPeriod::count()];
    $passwordHash = $this->callcenter->password;
    $this->seed(DemoSeeder::class);
    expect([Resident::count(), Ride::count(), BudgetPeriod::count()])->toBe($counts);
    expect($this->callcenter->fresh()->password)->toBe($passwordHash);
    $this->app->detectEnvironment(fn () => 'production');
    (new DemoSeeder)->run();
    expect([Resident::count(), Ride::count(), BudgetPeriod::count()])->toBe($counts);
});
