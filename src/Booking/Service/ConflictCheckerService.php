<?php

namespace App\Booking\Service;

use App\Auth\Entity\User;
use App\Booking\Repository\BookingRepository;
use App\Booking\Repository\ClassSessionRepository;
use InvalidArgumentException;

class ConflictCheckerService
{
    public function __construct(
        private readonly ClassSessionRepository $classSessionRepository,
        private readonly BookingRepository $bookingRepository
    ) {
    }

    /**
     * Rule 11: A coach cannot be assigned to two classes with overlapping schedules.
     */
    public function validateCoachAvailability(
        User $coach,
        string $dayOfWeek,
        \DateTimeInterface $startTime,
        \DateTimeInterface $endTime,
        ?int $excludeSessionId = null
    ): void {
        $conflicts = $this->classSessionRepository->findOverlappingCoachSessions(
            $coach,
            $dayOfWeek,
            $startTime,
            $endTime,
            $excludeSessionId
        );

        if (!empty($conflicts)) {
            $conflictName = $conflicts[0]->getName();
            throw new InvalidArgumentException(
                "Coach scheduling conflict (Rule 11): Coach '{$coach->getEmail()}' is already assigned to '{$conflictName}' at an overlapping time on {$dayOfWeek}."
            );
        }
    }

    /**
     * Rule 12: A room cannot host two classes with overlapping schedules.
     */
    public function validateRoomAvailability(
        string $room,
        string $dayOfWeek,
        \DateTimeInterface $startTime,
        \DateTimeInterface $endTime,
        ?int $excludeSessionId = null
    ): void {
        $conflicts = $this->classSessionRepository->findOverlappingRoomSessions(
            $room,
            $dayOfWeek,
            $startTime,
            $endTime,
            $excludeSessionId
        );

        if (!empty($conflicts)) {
            $conflictName = $conflicts[0]->getName();
            throw new InvalidArgumentException(
                "Room scheduling conflict (Rule 12): Room '{$room}' is already booked for class '{$conflictName}' at an overlapping time on {$dayOfWeek}."
            );
        }
    }

    /**
     * Rule 13: A member cannot book two classes with overlapping time slots.
     */
    public function validateMemberAvailability(
        User $member,
        string $dayOfWeek,
        \DateTimeInterface $startTime,
        \DateTimeInterface $endTime,
        ?int $excludeBookingId = null
    ): void {
        $conflicts = $this->bookingRepository->findOverlappingMemberBookings(
            $member,
            $dayOfWeek,
            $startTime,
            $endTime,
            $excludeBookingId
        );

        if (!empty($conflicts)) {
            $conflictClass = $conflicts[0]->getClassSession()->getName();
            throw new InvalidArgumentException(
                "Booking conflict (Rule 13): You already have a confirmed booking for '{$conflictClass}' during overlapping hours on {$dayOfWeek}."
            );
        }
    }
}
