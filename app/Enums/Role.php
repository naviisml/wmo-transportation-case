<?php

namespace App\Enums;

enum Role: string
{
    case Municipality = 'municipality';
    case Callcenter = 'callcenter';
    case TaxiCompany = 'taxi_company';

    public function canViewResidents(): bool
    {
        return $this === self::Municipality || $this === self::Callcenter;
    }
}
