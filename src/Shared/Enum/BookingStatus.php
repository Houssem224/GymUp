<?php

namespace App\Shared\Enum;

enum BookingStatus: string
{
    case CONFIRMED = 'CONFIRMED';
    case WAITLIST = 'WAITLIST';
    case CANCELLED = 'CANCELLED';
}
