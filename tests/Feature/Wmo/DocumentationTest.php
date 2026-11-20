<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

it('exports one specification and derives complete client collections', function () {
    $this->artisan('wmo:export-api')->assertSuccessful();
    $spec = json_decode(File::get(base_path('docs/openapi.json')), true, 512, JSON_THROW_ON_ERROR);
    expect($spec['openapi'])->toStartWith('3.1');
    expect($spec['paths'])->toHaveKeys(['/me', '/dashboard', '/residents', '/residents/{resident}', '/rides', '/tokens']);
    $booking = $spec['paths']['/rides']['post'];
    expect($booking['responses'])->toHaveKeys(['201', '401', '403', '409', '422']);
    expect(collect($booking['parameters'])->firstWhere('name', 'Idempotency-Key')['in'])->toBe('header');
    $schema = $spec['components']['schemas']['BookRideRequest'];
    expect($schema['properties'])->not->toHaveKey('idempotency_key');
    expect($spec['components']['schemas']['RideResource']['properties'])->not->toHaveKey('request_hash');
    $operations = collect($spec['paths'])->sum(fn ($path) => count(array_intersect(array_keys($path), ['get', 'post', 'delete', 'put', 'patch'])));
    $postman = json_decode(File::get(base_path('docs/postman/wmo-api.postman_collection.json')), true, 512, JSON_THROW_ON_ERROR);
    expect($postman['item'])->toHaveCount($operations);
    expect(glob(base_path('docs/bruno/*.bru')))->toHaveCount($operations);
    expect(File::exists(base_path('docs/bruno.zip')))->toBeTrue();
    $environment = json_decode(File::get(base_path('docs/postman/local.postman_environment.json')), true, 512, JSON_THROW_ON_ERROR);
    expect(collect($environment['values'])->firstWhere('key', 'token')['value'])->toBe('');
});

it('restricts documentation outside development', function () {
    $this->app->detectEnvironment(fn () => 'production');
    $this->get('/docs/api.json')->assertForbidden();
});
