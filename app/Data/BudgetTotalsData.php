<?php

namespace App\Data;

readonly class BudgetTotalsData
{
    public int $remainingMeters;

    public function __construct(
        public int $allocatedMeters,
        public int $consumedMeters,
    ) {
        $this->remainingMeters = $allocatedMeters - $consumedMeters;
    }
}
