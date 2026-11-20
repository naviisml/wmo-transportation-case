<?php

namespace Database\Seeders\Demo;

use Illuminate\Database\Seeder;

abstract class DemoDataSeeder extends Seeder
{
    final public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $this->seed();
    }

    abstract protected function seed(): void;
}
