<?php

namespace App\Models;

use App\Exceptions\BookingConflict;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetPeriod extends Model
{
    use HasFactory;

    protected $fillable = ['wmo_authorization_id', 'starts_on', 'ends_on', 'allocated_meters', 'consumed_meters'];

    protected function casts(): array
    {
        return [
            'starts_on' => 'immutable_date',
            'ends_on' => 'immutable_date',
            'allocated_meters' => 'integer',
            'consumed_meters' => 'integer',
        ];
    }

    public function authorization(): BelongsTo
    {
        return $this->belongsTo(WmoAuthorization::class, 'wmo_authorization_id');
    }

    public function rides(): HasMany
    {
        return $this->hasMany(Ride::class);
    }

    public function availableMeters(): int
    {
        return $this->allocated_meters - $this->consumed_meters;
    }

    public function consume(int $meters): void
    {
        if ($meters < 1 || $this->availableMeters() < $meters) {
            throw new BookingConflict('Insufficient distance budget.');
        }

        $this->increment('consumed_meters', $meters);
    }

    public function scopeCovering(Builder $query, CarbonImmutable $day): Builder
    {
        return $query->where('starts_on', '<=', $day->toDateString())
            ->where('ends_on', '>', $day->toDateString());
    }
}
