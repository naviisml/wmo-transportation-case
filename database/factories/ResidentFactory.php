<?php

namespace Database\Factories;

use App\Models\Resident;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResidentFactory extends Factory
{
    protected $model = Resident::class;

    public function definition(): array
    {
        return [
            'name' => 'Demo '.fake()->name(),
            'reference' => fake()->unique()->numerify('WMO-######'),
            'municipality' => Resident::MUNICIPALITY_UTRECHT,
        ];
    }
}
