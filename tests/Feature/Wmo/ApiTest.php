<?php

use App\Actions\BookRide;
use App\Actions\RenewBudgetPeriod;
use App\Models\Address;
use App\Models\BudgetPeriod;
use App\Models\ParcelAssignment;
use App\Models\Resident;
use App\Models\Ride;
use App\Models\TaxiCompany;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-20 12:00:00', 'Europe/Amsterdam'));
    config(['wmo.demo_password' => 'demo-assessment-only']);
    $this->seed(DemoSeeder::class);
    $this->callcenter = User::where('email', 'callcenter@example.test')->firstOrFail();
    $this->resident = Resident::where('reference', 'DEMO-001')->firstOrFail();
    $this->payload = ['resident_id' => $this->resident->id, 'origin' => 'Demo origin, Utrecht', 'destination' => 'Demo destination, Utrecht', 'scheduled_at' => now()->addDay()->toIso8601String(), 'distance_meters' => 10000];
});
function bookRequest($test, $payload = null, $key = 'test-booking')
{
    return $test->postJson('/api/v1/rides', $payload ?? $test->payload, ['Idempotency-Key' => $key]);
}

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

    Sanctum::actingAs($this->callcenter, ['residents:read', 'rides:book']);
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
    bookRequest($this, [...$this->payload, 'resident_id' => $otherMunicipality->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('resident_id');
});

it('enforces roles and token abilities as well as policies', function () {
    foreach (['gemeente', 'taxi-a', 'taxi-b'] as $account) {
        $user = User::where('email', $account.'@example.test')->firstOrFail();
        Sanctum::actingAs($user, ['*']);
        $this->getJson('/api/v1/residents')->assertForbidden();
        bookRequest($this)->assertForbidden();
        expect(Gate::forUser($user)->allows('viewAny', Resident::class))->toBeFalse();
    }Sanctum::actingAs($this->callcenter, ['me:read']);
    $this->getJson('/api/v1/residents')->assertForbidden();
    bookRequest($this)->assertForbidden();
});

it('books and consumes meters atomically with an immutable company snapshot', function () {
    $token = $this->callcenter->createToken('Callcenter booking', ['rides:book']);
    $this->withToken($token->plainTextToken);
    $budget = $this->resident->authorization->budgetPeriods()->orderByDesc('starts_on')->firstOrFail();
    $before = $budget->consumed_meters;
    $response = bookRequest($this)->assertCreated()->assertJsonPath('data.distance_meters', 10000);
    expect($budget->fresh()->consumed_meters)->toBe($before + 10000);
    $ride = Ride::findOrFail($response->json('data.id'));
    expect($ride->taxi_company_id)->toBe($this->resident->address->parcel->assignments()->first()->taxi_company_id);
    expect(DB::table('audit_entries')->where('action', 'ride.booked')->exists())->toBeTrue();

    foreach (User::whereIn('email', ['taxi-a@example.test', 'taxi-b@example.test'])->get() as $taxiUser) {
        $this->app['auth']->forgetGuards();
        $taxiToken = $taxiUser->createToken('Taxi dispatch', ['rides:read']);
        $date = $ride->scheduled_at->setTimezone('Europe/Amsterdam')->toDateString();
        $assignedRides = $this->withToken($taxiToken->plainTextToken)
            ->getJson('/api/v1/rides?from='.$date.'&to='.$date)
            ->assertOk();
        $assignedIds = collect($assignedRides->json('data'))->pluck('id');
        expect($assignedIds->contains($ride->id))->toBe($taxiUser->taxi_company_id === $ride->taxi_company_id);
    }
});

it('deduplicates booking retries and rejects key reuse with different data', function () {
    Sanctum::actingAs($this->callcenter, ['*']);
    $first = bookRequest($this)->assertCreated();
    bookRequest($this)->assertCreated()->assertJsonPath('data.id', $first->json('data.id'));
    expect(Ride::where('idempotency_key', 'test-booking')->count())->toBe(1);
    bookRequest($this, [...$this->payload, 'distance_meters' => 20000])->assertConflict();
});

it('rejects invalid inputs and client supplied company claims', function () {
    Sanctum::actingAs($this->callcenter, ['*']);
    $this->postJson('/api/v1/rides', $this->payload)->assertUnprocessable()->assertJsonValidationErrors('idempotency_key');
    foreach ([['distance_meters' => -1], ['taxi_company_id' => 1], ['scheduled_at' => '2020-01-01T00:00:00Z'], ['scheduled_at' => '2027-01-01 12:00:00'], ['resident_id' => 999999]] as $change) {
        bookRequest($this, [...$this->payload, ...$change])->assertUnprocessable();
    }
});

it('rejects absent or terminated authorizations', function (string $reference) {
    Sanctum::actingAs($this->callcenter, ['*']);
    $resident = Resident::where('reference', $reference)->firstOrFail();
    bookRequest($this, [...$this->payload, 'resident_id' => $resident->id])->assertConflict();
})->with(['DEMO-007', 'DEMO-008']);

it('rejects a future authorization start', function () {
    Sanctum::actingAs($this->callcenter, ['*']);
    $this->resident->authorization->update(['starts_on' => now()->addMonth()]);
    bookRequest($this)->assertConflict();
});

it('rejects insufficient budget without creating a ride', function () {
    Sanctum::actingAs($this->callcenter, ['*']);
    $r = Resident::where('reference', 'DEMO-006')->firstOrFail();
    $before = Ride::count();
    bookRequest($this, [...$this->payload, 'resident_id' => $r->id])->assertConflict();
    expect(Ride::count())->toBe($before);
});

it('rejects missing and expired parcel assignments', function () {
    Sanctum::actingAs($this->callcenter, ['*']);
    $address = $this->resident->address;
    $parcel = $address->parcel_id;
    $address->update(['parcel_id' => null]);
    bookRequest($this)->assertConflict();
    $address->update(['parcel_id' => $parcel]);
    ParcelAssignment::where('parcel_id', $parcel)->update(['ends_on' => now()->subDay()->toDateString()]);
    bookRequest($this)->assertConflict();
});

it('rolls back the ride when the budget update fails', function () {
    Sanctum::actingAs($this->callcenter, ['*']);
    $budget = $this->resident->authorization->budgetPeriods()->orderByDesc('starts_on')->first();
    $before = Ride::count();
    DB::unprepared("CREATE FUNCTION test_budget_failure() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN RAISE EXCEPTION 'simulated write failure'; END $$; CREATE TRIGGER test_budget_failure BEFORE UPDATE ON budget_periods FOR EACH ROW EXECUTE FUNCTION test_budget_failure()");
    try {
        app(BookRide::class)->handle($this->callcenter, $this->payload, 'rollback');
        $this->fail('Expected database failure');
    } catch (QueryException $e) {
        expect($e->getMessage())->toContain('simulated write failure');
    }expect(Ride::count())->toBe($before);
    expect($budget->fresh()->consumed_meters)->toBe($budget->consumed_meters);
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

it('authenticates a real bearer token and honors revocation', function () {
    $token = $this->callcenter->createToken('test', ['residents:read']);
    $this->withToken($token->plainTextToken)->getJson('/api/v1/residents')->assertOk();
    $this->withToken($token->plainTextToken)->postJson('/api/v1/rides', $this->payload)->assertForbidden();
    $token->accessToken->delete();
    $this->app['auth']->forgetGuards();
    $this->withToken($token->plainTextToken)->getJson('/api/v1/me')->assertUnauthorized();
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

it('rejects a ride beyond the generated annual budget boundary', function () {
    Sanctum::actingAs($this->callcenter, ['*']);
    $period = $this->resident->authorization->budgetPeriods()->orderByDesc('starts_on')->first();
    bookRequest($this, [...$this->payload, 'scheduled_at' => $period->ends_on->setTimezone('Europe/Amsterdam')->addDay()->toIso8601String()])->assertConflict();
});

it('uses the new budget and assignment from midnight on the renewal date in Amsterdam', function () {
    $this->travelTo(CarbonImmutable::parse('2026-12-31 12:00:00', 'Europe/Amsterdam'));
    app(RenewBudgetPeriod::class)->handle($this->resident->authorization->id, CarbonImmutable::parse('2027-01-01'));
    $assignment = $this->resident->address->parcel->assignments()->firstOrFail();
    $historicalRide = $this->resident->rides()->firstOrFail();
    $oldCompany = $historicalRide->taxi_company_id;
    $nextCompany = TaxiCompany::whereKeyNot($oldCompany)->firstOrFail();
    $assignment->update(['ends_on' => '2027-01-01']);
    $assignment->parcel->assignments()->create([
        'starts_on' => '2027-01-01', 'taxi_company_id' => $nextCompany->id,
    ]);
    Sanctum::actingAs($this->callcenter, ['*']);
    $response = bookRequest($this, [...$this->payload, 'scheduled_at' => '2027-01-01T00:00:00+01:00'])
        ->assertCreated()
        ->assertJsonPath('data.taxi_company.id', $nextCompany->id)
        ->assertJsonPath('data.budget.starts_on', '2027-01-01')
        ->assertJsonPath('data.budget.consumed_meters', 10000);
    expect($historicalRide->fresh()->taxi_company_id)->toBe($oldCompany);
});

it('replays an identical completed request even after its scheduled time', function () {
    Sanctum::actingAs($this->callcenter, ['*']);
    $first = bookRequest($this)->assertCreated();
    $this->travel(2)->days();
    bookRequest($this)->assertCreated()->assertJsonPath('data.id', $first->json('data.id'));
});

it('returns safe JSON for unexpected API failures even with debugging enabled', function () {
    config(['app.debug' => true]);
    Sanctum::actingAs($this->callcenter, ['*']);
    DB::unprepared("CREATE FUNCTION test_api_failure() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN RAISE EXCEPTION 'private database detail'; END $$; CREATE TRIGGER test_api_failure BEFORE UPDATE ON budget_periods FOR EACH ROW EXECUTE FUNCTION test_api_failure()");
    bookRequest($this)->assertStatus(500)
        ->assertExactJson(['message' => 'The API is temporarily unavailable.']);
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
