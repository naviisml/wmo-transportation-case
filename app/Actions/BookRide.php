<?php

namespace App\Actions;

use App\Data\BookRideData;
use App\Exceptions\BookingConflict;
use App\Exceptions\InvalidBooking;
use App\Models\BudgetPeriod;
use App\Models\ParcelAssignment;
use App\Models\Resident;
use App\Models\Ride;
use App\Models\User;
use App\Models\WmoAuthorization;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class BookRide
{
    /**
     * The account lock protects booking keys across residents.
     * Booking and renewal lock the authorization before the budget period.
     *
     * @throws BookingConflict
     * @throws InvalidBooking
     */
    public function handle(User $user, BookRideData $booking, string $key): Ride
    {
        $scheduled = $booking->scheduledAt;
        $data = $booking->toArray();
        $hash = $booking->requestHash();

        return DB::transaction(function () use ($user, $booking, $data, $key, $hash, $scheduled) {
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
                    throw new BookingConflict('Idempotency key was already used for a different request.');
                }

                return $existing;
            }
            if ($scheduled->isPast()) {
                throw new InvalidBooking('scheduled_at', 'The scheduled time must be in the future.');
            }

            $resident = Resident::query()
                ->inUtrecht()
                ->lockForUpdate()
                ->find($booking->residentId);

            if (! $resident) {
                throw new InvalidBooking('resident_id', __('validation.exists', ['attribute' => 'resident id']));
            }

            $authorization = WmoAuthorization::query()
                ->where('resident_id', $resident->id)
                ->lockForUpdate()
                ->first();
            $day = $scheduled->setTimezone('Europe/Amsterdam');
            if (! $authorization
                || ! $authorization->isActiveOn(CarbonImmutable::today('Europe/Amsterdam'))
                || ! $authorization->isActiveOn($day)) {
                throw new BookingConflict('No active authorization for this ride.');
            }
            $budget = BudgetPeriod::query()
                ->where('wmo_authorization_id', $authorization->id)
                ->covering($day)
                ->lockForUpdate()
                ->first();
            if (! $budget) {
                throw new BookingConflict('No budget period exists for the scheduled date.');
            }
            $address = $resident->address()
                ->lockForUpdate()
                ->first();
            if (! $address?->parcel_id) {
                throw new BookingConflict('Resident has no assigned residential parcel.');
            }

            $assignments = ParcelAssignment::query()
                ->where('parcel_id', $address->parcel_id)
                ->applicableOn($day)
                ->lockForUpdate()
                ->get();
            if ($assignments->count() !== 1) {
                throw new BookingConflict('No unambiguous taxi assignment for the scheduled date.');
            }
            $assignment = $assignments->first();
            $ride = Ride::create([
                ...$data,
                'scheduled_at' => $scheduled,
                'budget_period_id' => $budget->id,
                'wmo_authorization_id' => $authorization->id,
                'parcel_id' => $address->parcel_id,
                'parcel_assignment_id' => $assignment->id,
                'taxi_company_id' => $assignment->taxi_company_id,
                'booked_by' => $user->id,
                'idempotency_key' => $key,
                'request_hash' => $hash,
            ]);
            $budget->consume($booking->distanceMeters);
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
