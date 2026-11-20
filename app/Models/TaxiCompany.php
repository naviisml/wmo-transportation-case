<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxiCompany extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    protected function casts(): array
    {
        return [];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ParcelAssignment::class);
    }

    public function rides(): HasMany
    {
        return $this->hasMany(Ride::class);
    }
}
