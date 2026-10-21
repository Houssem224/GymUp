<?php

namespace App\Attendance\Service;

use App\Attendance\DTO\CheckInResult;
use App\Attendance\Entity\CheckIn;
use App\Attendance\Entity\Schedule;
use App\Attendance\Entity\Streak;
use App\Attendance\Repository\CheckInRepository;
use App\Attendance\Repository\ScheduleRepository;
use App\Attendance\Repository\StreakRepository;
use App\Auth\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class StreakService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CheckInRepository $checkInRepository,
        private readonly StreakRepository $streakRepository,
        private readonly ScheduleRepository $scheduleRepository
    ) {
    }

    /**
     * Process a QR scan check-in for a member and calculate their streak according to their schedule.
     */
    public function processCheckIn(User $member, ?\DateTimeImmutable $scannedAt = null): CheckInResult
    {
        $now = $scannedAt ?? new \DateTimeImmutable();
        $today = $now->setTime(0, 0, 0);
        $dayOfWeek = strtoupper($today->format('l')); // e.g. "MONDAY"

        // 1. Ensure Member has a Streak entity
        $streak = $this->getOrCreateStreak($member);

        // 2. Fetch Member's Weekly Schedule
        $schedule = $this->scheduleRepository->findByMember($member) ?? $member->getSchedule();
        $scheduledDays = $schedule ? $schedule->getWeekDays() : [];

        // 3. Check if member already checked in today
        $existingCheckInToday = $this->checkInRepository->findTodayCheckIn($member, $today);
        $isFirstCheckInToday = ($existingCheckInToday === null);

        // 4. Create the CheckIn record
        $checkIn = new CheckIn($member, $now);
        $this->entityManager->persist($checkIn);

        // 5. If member has no schedule defined yet
        if (empty($scheduledDays)) {
            $this->entityManager->flush();
            return new CheckInResult(
                success: true,
                checkIn: $checkIn,
                currentStreak: $streak->getCurrentStreak(),
                streakIncremented: false,
                streakBroken: false,
                message: 'Check-in recorded! Set your weekly attendance schedule in your profile to start building your streak.',
                activeScheduleDays: []
            );
        }

        $isScheduledDay = in_array($dayOfWeek, $scheduledDays, true);
        $lastSuccess = $streak->getLastSuccessDate();
        $streakIncremented = false;
        $streakBroken = false;
        $message = 'Check-in recorded successfully!';

        // If this is the member's very first successful check-in
        if ($lastSuccess === null) {
            if ($isScheduledDay) {
                $streak->setCurrentStreak(1);
                $streak->setLastSuccessDate($today);
                $streakIncremented = true;
                $message = "Welcome! You checked in on your scheduled {$dayOfWeek}. Your streak is now 1 day!";
            } else {
                $message = "Bonus visit! Today ({$dayOfWeek}) is not on your schedule. Streak stays at 0.";
            }
        } else {
            // Check if any scheduled days were missed between lastSuccess and today
            $missedScheduledDays = $this->hasMissedScheduledDays($member, $schedule, $lastSuccess, $today);

            if ($missedScheduledDays) {
                $streakBroken = true;
                if ($isScheduledDay) {
                    $streak->setCurrentStreak(1);
                    $streak->setLastSuccessDate($today);
                    $streakIncremented = true;
                    $message = "You missed a previous scheduled day, so your streak reset. You're back on track today! Streak: 1.";
                } else {
                    $streak->resetStreak();
                    $message = "You missed a previous scheduled day, so your streak reset. Today is a bonus visit.";
                }
            } else {
                // No scheduled days were missed
                if ($isScheduledDay) {
                    if ($isFirstCheckInToday && ($lastSuccess < $today)) {
                        $streak->incrementStreak();
                        $streak->setLastSuccessDate($today);
                        $streakIncremented = true;
                        $message = "Great work! You showed up on scheduled {$dayOfWeek}! Your streak is now {$streak->getCurrentStreak()} days in a row!";
                    } else {
                        // Already checked in today on this scheduled day
                        $message = "Welcome back today! Your {$streak->getCurrentStreak()}-day streak is already secure for {$dayOfWeek}.";
                    }
                } else {
                    // Visited on a non-scheduled day (bonus day, streak maintained without penalty or increment)
                    $message = "Great bonus workout on {$dayOfWeek}! Your current streak of {$streak->getCurrentStreak()} days is safe.";
                }
            }
        }

        $this->streakRepository->save($streak, false);
        $this->entityManager->flush();

        return new CheckInResult(
            success: true,
            checkIn: $checkIn,
            currentStreak: $streak->getCurrentStreak(),
            streakIncremented: $streakIncremented,
            streakBroken: $streakBroken,
            message: $message,
            activeScheduleDays: $scheduledDays
        );
    }

    /**
     * Checks if any scheduled day between $fromDate (exclusive) and $toDate (exclusive) had no check-in.
     */
    public function hasMissedScheduledDays(
        User $member,
        Schedule $schedule,
        \DateTimeImmutable $fromDate,
        \DateTimeImmutable $toDate
    ): bool {
        $from = $fromDate->setTime(0, 0, 0);
        $to = $toDate->setTime(0, 0, 0);

        // If toDate is on or before fromDate, or next consecutive day, no in-between days exist
        if ($from >= $to) {
            return false;
        }

        $cursor = $from->modify('+1 day');
        while ($cursor < $to) {
            $dayName = strtoupper($cursor->format('l'));
            if ($schedule->includesDay($dayName)) {
                // This was a scheduled day. Did the member check in on this day?
                $checkIn = $this->checkInRepository->findTodayCheckIn($member, $cursor);
                if ($checkIn === null) {
                    return true; // A scheduled day passed with no scan!
                }
            }
            $cursor = $cursor->modify('+1 day');
        }

        return false;
    }

    /**
     * Evaluates whether a streak is currently active or should be considered broken
     * when viewed today without a new scan yet.
     */
    public function getEffectiveStreak(User $member, ?\DateTimeImmutable $currentDate = null): int
    {
        $streak = $this->streakRepository->findByMember($member) ?? $member->getStreak();
        if (!$streak || $streak->getCurrentStreak() === 0) {
            return 0;
        }

        $schedule = $this->scheduleRepository->findByMember($member) ?? $member->getSchedule();
        if (!$schedule || empty($schedule->getWeekDays())) {
            return $streak->getCurrentStreak();
        }

        $lastSuccess = $streak->getLastSuccessDate();
        if (!$lastSuccess) {
            return 0;
        }

        $today = ($currentDate ?? new \DateTimeImmutable())->setTime(0, 0, 0);

        // Check if a scheduled day has already passed strictly before today without a check-in
        if ($this->hasMissedScheduledDays($member, $schedule, $lastSuccess, $today)) {
            return 0;
        }

        return $streak->getCurrentStreak();
    }

    public function getOrCreateStreak(User $member): Streak
    {
        $streak = $this->streakRepository->findByMember($member);
        if ($streak === null) {
            $streak = new Streak($member);
            $this->entityManager->persist($streak);
            $member->setStreak($streak);
        }

        return $streak;
    }
}
