<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParcelAssignment extends Model
{
    use HasFactory;

    protected $fillable = ['parcel_id', 'taxi_company_id', 'starts_on', 'ends_on'];

    protected function casts(): array
    {
        return ['starts_on' => 'immutable_date', 'ends_on' => 'immutable_date'];
    }

    public function parcel(): BelongsTo
    {
        return $this->belongsTo(Parcel::class);
    }

    public function taxiCompany(): BelongsTo
    {
        return $this->belongsTo(TaxiCompany::class);
    }

    public function scopeApplicableOn(Builder $query, CarbonImmutable $day): Builder
    {
        $date = $day->toDateString();

        return $query->where('starts_on', '<=', $date)
            ->where(fn ($query) => $query->whereNull('ends_on')->orWhere('ends_on', '>', $date));
    }
}
