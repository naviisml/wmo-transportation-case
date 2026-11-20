<?php

use App\Models\Ride;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
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

it('renders separate resident and booking pages with route identifiers only', function () {
    $this->actingAs(User::where('email', 'callcenter@example.test')->firstOrFail());
    $this->get('/residents')->assertInertia(fn (Assert $page) => $page->component('Residents/Index')->missing('residents'));
    $this->get('/residents/1')->assertInertia(fn (Assert $page) => $page->component('Residents/Show')->where('residentId', 1)->missing('resident'));
    $this->get('/residents/1/rides/create')->assertInertia(fn (Assert $page) => $page->component('Rides/Create')->where('residentId', 1)->missing('resident'));
    $this->get('/book-ride')->assertInertia(fn (Assert $page) => $page->component('Rides/SelectResident'));
});

it('authorizes page routes before rendering them', function (string $account, array $allowed, array $denied) {
    $this->actingAs(User::where('email', $account.'@example.test')->firstOrFail());
    foreach ($allowed as $path) {
        $this->get($path)->assertOk();
    }
    foreach ($denied as $path) {
        $this->get($path)->assertForbidden();
    }
})->with([
    ['gemeente', ['/dashboard', '/municipality', '/api-access'], ['/residents', '/residents/1', '/residents/1/rides/create', '/book-ride', '/rides']],
    ['callcenter', ['/dashboard', '/residents', '/book-ride', '/api-access'], ['/municipality', '/rides']],
    ['taxi-a', ['/dashboard', '/rides', '/api-access'], ['/municipality', '/residents', '/book-ride']],
]);
