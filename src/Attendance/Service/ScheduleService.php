<?php

namespace App\Attendance\Service;

use App\Attendance\Entity\Schedule;
use App\Attendance\Repository\ScheduleRepository;
use App\Auth\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

class ScheduleService
{
    private const ALLOWED_DAYS = [
        'MONDAY',
        'TUESDAY',
        'WEDNESDAY',
        'THURSDAY',
        'FRIDAY',
        'SATURDAY',
        'SUNDAY'
    ];

    public function __construct(
        private readonly ScheduleRepository $scheduleRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function getMemberSchedule(User $member): ?Schedule
    {
        return $this->scheduleRepository->findByMember($member) ?? $member->getSchedule();
    }

    /**
     * @param array<string> $days
     */
    public function updateMemberSchedule(User $member, array $days): Schedule
    {
        $normalizedDays = [];
        foreach ($days as $day) {
            $upper = strtoupper(trim((string)$day));
            if (!in_array($upper, self::ALLOWED_DAYS, true)) {
                throw new InvalidArgumentException("Invalid weekday: '{$day}'. Allowed values: " . implode(', ', self::ALLOWED_DAYS));
            }
            if (!in_array($upper, $normalizedDays, true)) {
                $normalizedDays[] = $upper;
            }
        }

        if (empty($normalizedDays)) {
            throw new InvalidArgumentException('A weekly schedule must include at least one workout day.');
        }

        $schedule = $this->getMemberSchedule($member);
        if ($schedule === null) {
            $schedule = new Schedule();
            $schedule->setMember($member);
            $member->setSchedule($schedule);
            $this->entityManager->persist($schedule);
        }

        $schedule->setWeekDays($normalizedDays);
        $this->entityManager->flush();

        return $schedule;
    }
}
