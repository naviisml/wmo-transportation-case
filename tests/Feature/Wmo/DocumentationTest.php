<?php

use App\Models\Resident;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('publishes the API through Scramble OpenAPI', function () {
    $response = $this->get('/docs/api.json')->assertSuccessful();
    $spec = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

    expect($spec['openapi'])->toStartWith('3.1');
    expect($spec['paths'])->toHaveKeys(['/me', '/dashboard', '/residents', '/residents/{resident}', '/rides', '/tokens']);
    $booking = $spec['paths']['/rides']['post'];
    expect($booking['responses'])->toHaveKeys(['201', '401', '403', '409', '422']);
    expect(collect($booking['parameters'])->firstWhere('name', 'Idempotency-Key')['in'])->toBe('header');
    expect($spec['components']['schemas']['BookRideRequest']['properties'])->not->toHaveKey('idempotency_key');
    expect($spec['components']['schemas']['RideResource']['properties'])->not->toHaveKey('request_hash');
});

it('restricts documentation outside development', function () {
    $this->app->detectEnvironment(fn () => 'production');
    $this->get('/docs/api.json')->assertForbidden();
});

it('defaults and hardens resident municipalities to Utrecht', function () {
    $resident = Resident::factory()->create();
    expect($resident->municipality)->toBe(Resident::MUNICIPALITY_UTRECHT);

    expect(fn () => Resident::factory()->create(['municipality' => 'other']))
        ->toThrow(QueryException::class);

    expect(fn () => DB::table('residents')->insert([
        'name' => 'Null Municipality',
        'reference' => 'WMO-NULL',
        'municipality' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});
