<?php

namespace App\Shared\Enum;

enum PositionType: string
{
    case COACH = 'COACH';
    case RECEPTIONIST = 'RECEPTIONIST';

    public function toUserRole(): UserRole
    {
        return match ($this) {
            self::COACH => UserRole::COACH,
            self::RECEPTIONIST => UserRole::RECEPTIONIST,
        };
    }
}
