<?php

namespace App\Booking\Service;

use App\Auth\Entity\User;
use App\Booking\Entity\Booking;
use App\Booking\Entity\ClassSession;
use App\Booking\Repository\BookingRepository;
use App\Booking\Repository\ClassSessionRepository;
use App\Membership\Repository\SubscriptionRepository;
use App\Shared\Enum\BookingStatus;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use LogicException;

class BookingService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ClassSessionRepository $classSessionRepository,
        private readonly BookingRepository $bookingRepository,
        private readonly SubscriptionRepository $subscriptionRepository,
        private readonly ConflictCheckerService $conflictChecker
    ) {
    }

    /**
     * Create a new class session with coach and room conflict prevention (Rules 11 & 12).
     */
    public function createClassSession(
        string $name,
        User $coach,
        string $room,
        string $dayOfWeek,
        \DateTimeImmutable $startTime,
        \DateTimeImmutable $endTime,
        int $capacity
    ): ClassSession {
        if ($startTime >= $endTime) {
            throw new InvalidArgumentException('Start time must be before end time.');
        }

        // Rule 11: Coach cannot be double-booked
        $this->conflictChecker->validateCoachAvailability($coach, $dayOfWeek, $startTime, $endTime);

        // Rule 12: Room cannot be double-booked
        $this->conflictChecker->validateRoomAvailability($room, $dayOfWeek, $startTime, $endTime);

        $session = new ClassSession();
        $session->setName($name)
            ->setCoach($coach)
            ->setRoom($room)
            ->setDayOfWeek($dayOfWeek)
            ->setStartTime($startTime)
            ->setEndTime($endTime)
            ->setCapacity($capacity);

        $this->classSessionRepository->save($session);

        return $session;
    }

    /**
     * Book a class for a member enforcing Rules 7, 9, and 13.
     */
    public function bookClass(User $member, ClassSession $classSession): Booking
    {
        // 1. Rule 7: Membership must be active and plan must include classes
        $subscription = $this->subscriptionRepository->findActiveSubscription($member);
        if (!$subscription) {
            throw new LogicException('Booking rejected (Rule 7): You must have an active membership subscription to book a class.');
        }

        $plan = $subscription->getPlan();
        if (!$plan || !$plan->getAccessScope()->allowsClasses()) {
            throw new LogicException('Booking rejected (Rule 7): Your membership plan does not include group class access.');
        }

        // 2. Prevent duplicate booking for the same class
        $existing = $this->bookingRepository->findExistingBooking($member, $classSession);
        if ($existing !== null) {
            throw new LogicException("You already have an active booking or waitlist entry for this class.");
        }

        // 3. Rule 13: Member cannot book two classes with overlapping time slots
        $this->conflictChecker->validateMemberAvailability(
            $member,
            $classSession->getDayOfWeek(),
            $classSession->getStartTime(),
            $classSession->getEndTime()
        );

        // 4. Rule 9: Check capacity; if full, place on waitlist
        $confirmedCount = $this->bookingRepository->countConfirmed($classSession);
        $status = ($confirmedCount >= $classSession->getCapacity()) 
            ? BookingStatus::WAITLIST 
            : BookingStatus::CONFIRMED;

        $booking = new Booking($member, $classSession, $status);
        $this->bookingRepository->save($booking);

        return $booking;
    }

    /**
     * Cancel a booking and automatically promote the next waitlisted member if applicable.
     */
    public function cancelBooking(User $member, Booking $booking): ?Booking
    {
        if ($booking->getMember() !== $member) {
            throw new LogicException('You can only cancel your own bookings.');
        }

        if ($booking->isCancelled()) {
            throw new LogicException('This booking is already cancelled.');
        }

        $wasConfirmed = $booking->isConfirmed();
        $booking->setStatus(BookingStatus::CANCELLED);
        $this->entityManager->persist($booking);

        $promotedBooking = null;

        // If a confirmed spot freed up, promote the earliest waitlisted member
        if ($wasConfirmed) {
            $nextInWaitlist = $this->bookingRepository->findFirstWaitlisted($booking->getClassSession());
            if ($nextInWaitlist !== null) {
                $nextInWaitlist->setStatus(BookingStatus::CONFIRMED);
                $this->entityManager->persist($nextInWaitlist);
                $promotedBooking = $nextInWaitlist;
            }
        }

        $this->entityManager->flush();

        return $promotedBooking;
    }
}
