<?php

namespace App\Shared\Enum;

enum JobPostingStatus: string
{
    case OPEN = 'OPEN';
    case CLOSED = 'CLOSED';
}
