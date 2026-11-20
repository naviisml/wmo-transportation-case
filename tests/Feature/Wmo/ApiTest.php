<?php

use App\Models\Address;
use App\Models\BudgetPeriod;
use App\Models\Resident;
use App\Models\Ride;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-20 12:00:00', 'Europe/Amsterdam'));
    config(['wmo.demo_password' => 'demo-assessment-only']);
    $this->seed(DemoSeeder::class);
    $this->callcenter = User::where('email', 'callcenter@example.test')->firstOrFail();
    $this->resident = Resident::where('reference', 'DEMO-001')->firstOrFail();
});

it('requires authentication and hides public registration', function () {
    $this->getJson('/api/v1/me')->assertUnauthorized();
    $this->get('/register')->assertNotFound();
    $this->post('/register', [])->assertNotFound();
});

it('searches and paginates minimized resident data', function () {
    Sanctum::actingAs($this->callcenter, ['residents:read']);
    $this->getJson('/api/v1/residents')->assertOk()->assertJsonCount(15, 'data')->assertJsonPath('meta.total', 24);
    $this->getJson('/api/v1/residents?search=DEMO-001')->assertJsonCount(1, 'data')->assertJsonMissingPath('data.0.bsn')->assertJsonMissingPath('data.0.medical_information');
    $this->getJson('/api/v1/residents/'.$this->resident->id)->assertOk()->assertJsonPath('data.authorization.budget_periods.0.allocated_meters', 1500000);
    $this->getJson('/api/v1/residents/999999')->assertNotFound();
});

it('lists the whole Utrecht register regardless of Wmo status or postal locality', function () {
    $otherMunicipality = Resident::factory()->create(['municipality' => 'other']);
    $unclassified = Resident::factory()->create();
    $withoutAuthorization = Resident::where('reference', 'DEMO-008')->firstOrFail();
    $withoutAuthorization->address()->update(['city' => 'Vleuten']);
    $withoutAddress = Resident::factory()->create(['municipality' => Resident::MUNICIPALITY_UTRECHT]);
    Address::factory()->for($otherMunicipality)->create(['city' => 'Utrecht']);

    Sanctum::actingAs($this->callcenter, ['residents:read']);
    $first = $this->getJson('/api/v1/residents')
        ->assertOk()
        ->assertJsonPath('meta.total', 25)
        ->assertJsonCount(15, 'data');
    $second = $this->getJson($first->json('links.next'))
        ->assertOk()
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('links.next', null);

    $ids = collect([...$first->json('data'), ...$second->json('data')])->pluck('id');
    expect($ids->unique()->count())->toBe(25);
    expect($ids->all())->toContain($withoutAuthorization->id, $withoutAddress->id);
    expect($ids->all())->not->toContain($otherMunicipality->id, $unclassified->id);

    $this->getJson('/api/v1/residents/'.$withoutAuthorization->id)
        ->assertOk()
        ->assertJsonPath('data.authorization', null);
    $this->getJson('/api/v1/residents/'.$otherMunicipality->id)->assertNotFound();
});

it('isolates taxi companies before pagination and omits budget data', function () {
    foreach (['taxi-a', 'taxi-b'] as $account) {
        $user = User::where('email', $account.'@example.test')->first();
        Sanctum::actingAs($user, ['rides:read']);
        $response = $this->getJson('/api/v1/rides')->assertOk()->assertJsonMissingPath('data.0.budget');
        foreach ($response->json('data') as $ride) {
            expect($ride['taxi_company']['id'])->toBe($user->taxi_company_id);
        }$other = Ride::where('taxi_company_id', '!=', $user->taxi_company_id)->first();
        expect(Gate::forUser($user)->allows('view', $other))->toBeFalse();
        $this->getJson('/api/v1/rides?status=invalid')->assertUnprocessable();
        $this->getJson('/api/v1/rides?from=2035-01-01')->assertJsonCount(0, 'data');
    }
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

it('accepts an independent through-date filter', function () {
    Sanctum::actingAs(User::where('email', 'taxi-a@example.test')->firstOrFail(), ['rides:read']);
    $this->getJson('/api/v1/rides?to=2035-01-01')->assertOk();
    $this->getJson('/api/v1/rides?from=2035-01-02&to=2035-01-01')->assertUnprocessable();
});

it('preserves booked-by history when an account requests deletion', function () {
    $this->actingAs($this->callcenter)->delete('/settings/profile', ['password' => 'demo-assessment-only'])
        ->assertSessionHasErrors('password');
    expect($this->callcenter->fresh())->not->toBeNull();
});
