<?php

namespace App\Booking\Controller\Member;

use App\Auth\Entity\User;
use App\Booking\Repository\BookingRepository;
use App\Booking\Repository\ClassSessionRepository;
use App\Booking\Service\BookingService;
use App\Shared\Enum\BookingStatus;
use InvalidArgumentException;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/member', name: 'api_member_')]
#[IsGranted('ROLE_MEMBER')]
class BookingController extends AbstractController
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly BookingRepository $bookingRepository,
        private readonly ClassSessionRepository $classSessionRepository
    ) {
    }

    #[Route('/classes', name: 'classes_browse', methods: ['GET'])]
    public function browseClasses(): JsonResponse
    {
        $classes = $this->classSessionRepository->findAll();

        $data = array_map(function ($c) {
            return [
                'id' => $c->getId(),
                'name' => $c->getName(),
                'coachName' => $c->getCoach()->getFullName() ?? $c->getCoach()->getEmail(),
                'room' => $c->getRoom(),
                'dayOfWeek' => $c->getDayOfWeek(),
                'startTime' => $c->getStartTime()->format('H:i'),
                'endTime' => $c->getEndTime()->format('H:i'),
                'capacity' => $c->getCapacity(),
                'availableSpots' => max(0, $c->getCapacity() - $c->getConfirmedBookingsCount()),
                'isFull' => $c->isFull(),
            ];
        }, $classes);

        return $this->json($data);
    }

    #[Route('/classes/{id}/book', name: 'class_book', methods: ['POST'])]
    public function book(int $id, #[CurrentUser] ?User $member): JsonResponse
    {
        if (!$member) {
            return $this->json(['error' => 'Authentication required.'], Response::HTTP_UNAUTHORIZED);
        }

        $session = $this->classSessionRepository->find($id);
        if (!$session) {
            return $this->json(['error' => 'Class session not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $booking = $this->bookingService->bookClass($member, $session);

            $isWaitlist = $booking->getStatus() === BookingStatus::WAITLIST;
            $message = $isWaitlist
                ? "Class is currently full. You have been placed on the waitlist!"
                : "Booking confirmed! See you at {$session->getName()}.";

            return $this->json([
                'message' => $message,
                'booking' => [
                    'id' => $booking->getId(),
                    'classId' => $session->getId(),
                    'className' => $session->getName(),
                    'status' => $booking->getStatus()->value,
                    'dayOfWeek' => $session->getDayOfWeek(),
                    'time' => $session->getStartTime()->format('H:i') . ' - ' . $session->getEndTime()->format('H:i'),
                    'bookedAt' => $booking->getBookedAt()->format(\DateTimeInterface::ATOM),
                ]
            ], Response::HTTP_CREATED);
        } catch (InvalidArgumentException|LogicException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/bookings/{id}/cancel', name: 'booking_cancel', methods: ['POST', 'PATCH'])]
    public function cancel(int $id, #[CurrentUser] ?User $member): JsonResponse
    {
        if (!$member) {
            return $this->json(['error' => 'Authentication required.'], Response::HTTP_UNAUTHORIZED);
        }

        $booking = $this->bookingRepository->find($id);
        if (!$booking) {
            return $this->json(['error' => 'Booking not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $promoted = $this->bookingService->cancelBooking($member, $booking);

            return $this->json([
                'message' => 'Booking cancelled successfully.',
                'promotedWaitlistBookingId' => $promoted?->getId(),
            ]);
        } catch (LogicException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/bookings', name: 'booking_history', methods: ['GET'])]
    public function myBookings(#[CurrentUser] ?User $member): JsonResponse
    {
        if (!$member) {
            return $this->json(['error' => 'Authentication required.'], Response::HTTP_UNAUTHORIZED);
        }

        $bookings = $this->bookingRepository->findByMember($member);

        $data = array_map(function ($b) {
            $cs = $b->getClassSession();
            return [
                'id' => $b->getId(),
                'classId' => $cs->getId(),
                'className' => $cs->getName(),
                'coachName' => $cs->getCoach()->getFullName() ?? $cs->getCoach()->getEmail(),
                'room' => $cs->getRoom(),
                'dayOfWeek' => $cs->getDayOfWeek(),
                'time' => $cs->getStartTime()->format('H:i') . ' - ' . $cs->getEndTime()->format('H:i'),
                'status' => $b->getStatus()->value,
                'bookedAt' => $b->getBookedAt()->format(\DateTimeInterface::ATOM),
            ];
        }, $bookings);

        return $this->json($data);
    }
}
