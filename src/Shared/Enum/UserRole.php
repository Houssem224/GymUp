<?php

namespace App\Shared\Enum;

enum UserRole: string
{
    case ADMIN = 'ROLE_ADMIN';
    case RECEPTIONIST = 'ROLE_RECEPTIONIST';
    case COACH = 'ROLE_COACH';
    case MEMBER = 'ROLE_MEMBER';
}
