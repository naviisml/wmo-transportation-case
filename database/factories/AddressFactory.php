<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Resident;
use Illuminate\Database\Eloquent\Factories\Factory;

class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return ['resident_id' => Resident::factory(), 'street' => 'Voorbeeldstraat', 'house_number' => (string) fake()->numberBetween(1, 200), 'postal_code' => '3500 AA', 'city' => 'Utrecht'];
    }
}
