<?php

namespace App\Enums;

enum RideStatus: string
{
    case Booked = 'booked';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
