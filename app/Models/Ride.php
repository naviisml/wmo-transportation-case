<?php

namespace App\Models;

use App\Enums\RideStatus;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ride extends Model
{
    use HasFactory;

    protected $attributes = ['status' => 'booked'];

    protected $fillable = [
        'resident_id',
        'budget_period_id',
        'parcel_id',
        'parcel_assignment_id',
        'taxi_company_id',
        'booked_by',
        'origin',
        'destination',
        'scheduled_at',
        'distance_meters',
        'status',
        'idempotency_key',
        'request_hash',
    ];

    protected function casts(): array
    {
        return [
            'distance_meters' => 'integer',
            'scheduled_at' => 'immutable_datetime',
            'status' => RideStatus::class,
        ];
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }

    public function taxiCompany(): BelongsTo
    {
        return $this->belongsTo(TaxiCompany::class);
    }

    public function budgetPeriod(): BelongsTo
    {
        return $this->belongsTo(BudgetPeriod::class);
    }

    public function parcelAssignment(): BelongsTo
    {
        return $this->belongsTo(ParcelAssignment::class);
    }

    public function parcel(): BelongsTo
    {
        return $this->belongsTo(Parcel::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where('taxi_company_id', $user->role === Role::TaxiCompany ? $user->taxi_company_id : -1);
    }
}
