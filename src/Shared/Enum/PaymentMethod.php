<?php

namespace App\Shared\Enum;

enum PaymentMethod: string
{
    case ONLINE = 'ONLINE';
    case CASH = 'CASH';
}
