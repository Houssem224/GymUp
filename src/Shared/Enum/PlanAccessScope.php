<?php

namespace App\Shared\Enum;

enum PlanAccessScope: string
{
    case OPEN_GYM = 'OPEN_GYM';
    case CLASSES = 'CLASSES';
    case BOTH = 'BOTH';

    public function allowsOpenGym(): bool
    {
        return $this === self::OPEN_GYM || $this === self::BOTH;
    }

    public function allowsClasses(): bool
    {
        return $this === self::CLASSES || $this === self::BOTH;
    }
}
