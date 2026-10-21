<?php

namespace App\Shared\Enum;

enum SubscriptionStatus: string
{
    case ACTIVE = 'ACTIVE';
    case PENDING = 'PENDING';
    case EXPIRED = 'EXPIRED';
    case CANCELLED = 'CANCELLED';
}
