<?php

namespace App\Attendance\DTO;

use App\Attendance\Entity\CheckIn;

class CheckInResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?CheckIn $checkIn,
        public readonly int $currentStreak,
        public readonly bool $streakIncremented,
        public readonly bool $streakBroken,
        public readonly string $message,
        public readonly ?array $activeScheduleDays = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'checkInId' => $this->checkIn?->getId(),
            'scannedAt' => $this->checkIn?->getScannedAt()->format(\DateTimeInterface::ATOM),
            'currentStreak' => $this->currentStreak,
            'streakIncremented' => $this->streakIncremented,
            'streakBroken' => $this->streakBroken,
            'message' => $this->message,
            'activeScheduleDays' => $this->activeScheduleDays ?? [],
        ];
    }
}
