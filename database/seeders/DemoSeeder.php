<?php

namespace Database\Seeders;

use Database\Seeders\Demo\DemoDataSeeder;
use Illuminate\Support\Facades\DB;

class DemoSeeder extends DemoDataSeeder
{
    protected function seed(): void
    {
        DB::transaction(fn () => $this->call([
            Demo\TransportSeeder::class,
            Demo\UserSeeder::class,
            Demo\ResidentSeeder::class,
            Demo\WmoAuthorizationSeeder::class,
            Demo\RideSeeder::class,
        ]));
    }
}
