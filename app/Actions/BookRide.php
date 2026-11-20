<?php

namespace App\Actions;

use App\Models\BudgetPeriod;
use App\Models\ParcelAssignment;
use App\Models\Resident;
use App\Models\Ride;
use App\Models\User;
use App\Models\WmoAuthorization;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class BookRide
{
    /**
     * The account lock protects booking keys across residents.
     * Booking and renewal lock the authorization before the budget period.
     *
     * @param  array{
     *     resident_id: int,
     *     origin: string,
     *     destination: string,
     *     scheduled_at: string,
     *     distance_meters: int
     * }  $data
     *
     * @throws ConflictHttpException
     * @throws ValidationException
     */
    public function handle(User $user, array $data, string $key): Ride
    {
        $scheduled = CarbonImmutable::parse($data['scheduled_at'])->utc();
        $data['scheduled_at'] = $scheduled->toIso8601String();
        ksort($data);
        $hash = hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($user, $data, $key, $hash, $scheduled) {
            User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();
            $existing = Ride::query()
                ->where('booked_by', $user->id)
                ->where('idempotency_key', $key)
                ->first();

            if ($existing) {
                if (! hash_equals($existing->request_hash, $hash)) {
                    throw new ConflictHttpException('Idempotency key was already used for a different request.');
                }

                return $existing;
            }
            if ($scheduled->isPast()) {
                throw ValidationException::withMessages(['scheduled_at' => 'The scheduled time must be in the future.']);
            }

            $resident = Resident::query()
                ->inUtrecht()
                ->lockForUpdate()
                ->find($data['resident_id']);

            if (! $resident) {
                throw ValidationException::withMessages(['resident_id' => __('validation.exists', ['attribute' => 'resident id'])]);
            }

            $authorization = WmoAuthorization::query()
                ->where('resident_id', $resident->id)
                ->lockForUpdate()
                ->first();
            $day = $scheduled->setTimezone('Europe/Amsterdam');
            if (! $authorization
                || ! $authorization->isActiveOn(CarbonImmutable::today('Europe/Amsterdam'))
                || ! $authorization->isActiveOn($day)) {
                throw new ConflictHttpException('No active authorization for this ride.');
            }
            $budget = BudgetPeriod::query()
                ->where('wmo_authorization_id', $authorization->id)
                ->covering($day)
                ->lockForUpdate()
                ->first();
            if (! $budget) {
                throw new ConflictHttpException('No budget period exists for the scheduled date.');
            }
            if ($budget->allocated_meters - $budget->consumed_meters < $data['distance_meters']) {
                throw new ConflictHttpException('Insufficient distance budget.');
            }

            $address = $resident->address()
                ->lockForUpdate()
                ->first();
            if (! $address?->parcel_id) {
                throw new ConflictHttpException('Resident has no assigned residential parcel.');
            }

            $assignments = ParcelAssignment::query()
                ->where('parcel_id', $address->parcel_id)
                ->applicableOn($day)
                ->lockForUpdate()
                ->get();
            if ($assignments->count() !== 1) {
                throw new ConflictHttpException('No unambiguous taxi assignment for the scheduled date.');
            }
            $assignment = $assignments->first();
            $ride = Ride::create([
                ...$data,
                'scheduled_at' => $scheduled,
                'budget_period_id' => $budget->id,
                'parcel_id' => $address->parcel_id,
                'parcel_assignment_id' => $assignment->id,
                'taxi_company_id' => $assignment->taxi_company_id,
                'booked_by' => $user->id,
                'idempotency_key' => $key,
                'request_hash' => $hash,
            ]);
            $budget->increment('consumed_meters', $data['distance_meters']);
            DB::table('audit_entries')->insert([
                'user_id' => $user->id,
                'action' => 'ride.booked',
                'resource_type' => 'ride',
                'resource_id' => $ride->id,
            ]);

            return $ride;
        }, 3);
    }
}
