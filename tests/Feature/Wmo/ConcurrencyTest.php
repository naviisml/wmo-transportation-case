<?php

use App\Actions\BookRide;
use App\Data\BookRideData;
use App\Enums\Role;
use App\Exceptions\BookingConflict;
use App\Models\Resident;
use App\Models\Ride;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;

uses(DatabaseMigrations::class);
it('serializes competing bookings and concurrent retries on PostgreSQL', function (bool $sameKey) {
    $this->travelTo(CarbonImmutable::parse('2026-11-20 12:00:00', 'Europe/Amsterdam'));
    config(['wmo.demo_password' => 'demo-assessment-only']);
    $this->seed(DemoSeeder::class);
    $resident = Resident::where('reference', 'DEMO-001')->firstOrFail();
    $user = User::where('email', 'callcenter@example.test')->firstOrFail();
    $budget = $resident->authorization->budgetPeriods()->latest('starts_on')->firstOrFail();
    $budget->update(['allocated_meters' => 22000, 'consumed_meters' => 12000]);
    $payload = ['resident_id' => $resident->id, 'origin' => 'Demo origin', 'destination' => 'Demo destination', 'scheduled_at' => now()->addDay()->toIso8601String(), 'distance_meters' => 8000];
    $otherUser = User::factory()->create(['role' => Role::Callcenter]);
    $userIds = [$user->id, $sameKey ? $user->id : $otherUser->id];
    $directory = sys_get_temp_dir().'/wmo-concurrency-'.bin2hex(random_bytes(8));
    mkdir($directory);
    $children = [];
    // Give each worker its own PostgreSQL connection.
    DB::disconnect();
    for ($i = 0; $i < 2; $i++) {
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new RuntimeException('fork failed');
        }
        if ($pid === 0) {
            DB::purge();
            $limit = microtime(true) + 10;
            while (! is_file($directory.'/start')) {
                if (microtime(true) > $limit) {
                    exit(2);
                }usleep(1000);
            }
            try {
                $ride = app(BookRide::class)->handle(User::findOrFail($userIds[$i]), BookRideData::fromArray($payload), $sameKey ? 'concurrent-retry' : 'concurrent-'.$i);
                file_put_contents($directory.'/'.$i, json_encode(['id' => $ride->id]));
            } catch (BookingConflict $e) {
                file_put_contents($directory.'/'.$i, json_encode(['conflict' => true]));
            } catch (Throwable $e) {
                file_put_contents($directory.'/'.$i, json_encode(['error' => $e->getMessage()]));
            }
            DB::disconnect();
            exit(0);
        }
        $children[] = $pid;
    }
    touch($directory.'/start');
    foreach ($children as $pid) {
        pcntl_waitpid($pid, $status);
        expect(pcntl_wexitstatus($status))->toBe(0);
    }
    DB::purge();
    $results = [];
    for ($i = 0; $i < 2; $i++) {
        $results[] = json_decode(file_get_contents($directory.'/'.$i), true, 512, JSON_THROW_ON_ERROR);
    }
    try {
        foreach ($results as $result) {
            expect($result)->not->toHaveKey('error');
        }expect($budget->fresh()->consumed_meters)->toBe(20000);
        expect(Ride::where('idempotency_key', 'like', 'concurrent%')->count())->toBe(1);
        if ($sameKey) {
            expect($results[0]['id'])->toBe($results[1]['id']);
        } else {
            expect(count(array_filter($results, fn ($r) => isset($r['conflict']))))->toBe(1);
        }
    } finally {
        foreach (glob($directory.'/*') as $file) {
            unlink($file);
        }rmdir($directory);
    }
})->with([false, true]);
