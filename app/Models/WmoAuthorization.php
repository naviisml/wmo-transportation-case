<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WmoAuthorization extends Model
{
    use HasFactory;

    protected $fillable = ['resident_id', 'starts_on', 'terminated_on', 'annual_meters'];

    protected function casts(): array
    {
        return [
            'starts_on' => 'immutable_date',
            'terminated_on' => 'immutable_date',
            'annual_meters' => 'integer',
        ];
    }

    public function isActiveOn(CarbonImmutable $day): bool
    {
        $date = $day->toDateString();

        return $this->starts_on->toDateString() <= $date
            && ($this->terminated_on === null || $this->terminated_on->toDateString() > $date);
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }

    public function budgetPeriods(): HasMany
    {
        return $this->hasMany(BudgetPeriod::class);
    }

    public function scopeActiveOn(Builder $query, CarbonImmutable $day): Builder
    {
        $date = $day->toDateString();

        return $query->where('starts_on', '<=', $date)
            ->where(fn ($query) => $query->whereNull('terminated_on')->orWhere('terminated_on', '>', $date));
    }
}
