<?php

use App\Models\Ride;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['wmo.demo_password' => 'demo-assessment-only']);
    $this->seed(DemoSeeder::class);
});

it('provides municipal aggregates without granting resident access', function () {
    $user = User::where('email', 'gemeente@example.test')->firstOrFail();
    Sanctum::actingAs($user, ['dashboard:read']);
    $response = $this->getJson('/api/v1/dashboard')
        ->assertOk()
        ->assertJsonPath('data.role', 'municipality')
        ->assertJsonPath('data.residents_total', 24)
        ->assertJsonPath('data.active_authorizations', 18)
        ->assertJsonPath('data.terminated_authorizations', 3)
        ->assertJsonPath('data.without_authorization', 3)
        ->assertJsonPath('data.taxi_companies', 2)
        ->assertJsonPath('data.parcels', 2)
        ->assertJsonPath('data.uncovered_parcels', 0)
        ->assertJsonPath('data.budget.allocated_meters', 16_584_000);
    expect($response->json('data'))->not->toHaveKeys(['residents', 'rides', 'name', 'address', 'reference']);
    $this->getJson('/api/v1/residents')->assertForbidden();
    $this->getJson('/api/v1/rides')->assertForbidden();
});

it('scopes taxi dashboard totals to the current company', function () {
    $user = User::where('email', 'taxi-a@example.test')->firstOrFail();
    Sanctum::actingAs($user, ['dashboard:read']);
    $this->getJson('/api/v1/dashboard')
        ->assertOk()
        ->assertJsonPath('data.rides_total', Ride::where('taxi_company_id', $user->taxi_company_id)->count())
        ->assertJsonMissingPath('data.residents_total')
        ->assertJsonMissingPath('data.budget')
        ->assertJsonMissingPath('data.taxi_companies');
});

it('uses all relevant records rather than a table page for callcenter totals', function () {
    $user = User::where('email', 'callcenter@example.test')->firstOrFail();
    Sanctum::actingAs($user, ['dashboard:read']);
    $this->getJson('/api/v1/dashboard')
        ->assertOk()
        ->assertJsonPath('data.residents_total', 24)
        ->assertJsonPath('data.budget.allocated_meters', 16_584_000)
        ->assertJsonMissingPath('data.uncovered_parcels');
});

it('requires authentication and a dashboard token ability', function () {
    $this->getJson('/api/v1/dashboard')->assertUnauthorized();
    Sanctum::actingAs(User::factory()->create(), ['me:read']);
    $this->getJson('/api/v1/dashboard')->assertForbidden();
});
