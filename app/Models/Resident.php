<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Resident extends Model
{
    use HasFactory;

    public const MUNICIPALITY_UTRECHT = 'utrecht';

    protected $fillable = ['name', 'reference', 'municipality'];

    public function address(): HasOne
    {
        return $this->hasOne(Address::class);
    }

    public function authorization(): HasOne
    {
        return $this->hasOne(WmoAuthorization::class);
    }

    public function rides(): HasMany
    {
        return $this->hasMany(Ride::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->inUtrecht()->when($user->role !== Role::Callcenter, fn ($query) => $query->whereRaw('false'));
    }

    public function scopeInUtrecht(Builder $query): Builder
    {
        return $query->where('municipality', self::MUNICIPALITY_UTRECHT);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if ($search === null || $search === '') {
            return $query;
        }
        $pattern = '%'.addcslashes($search, '%_\\').'%';

        return $query->where(fn ($query) => $query
            ->where('name', 'ilike', $pattern)
            ->orWhere('reference', 'ilike', $pattern));
    }
}
