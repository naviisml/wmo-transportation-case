<?php

namespace App\Enums;

enum Role: string
{
    case Municipality = 'municipality';
    case Callcenter = 'callcenter';
    case TaxiCompany = 'taxi_company';
}
