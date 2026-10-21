<?php

namespace App\Booking\Controller\Coach;

use App\Auth\Entity\User;
use App\Booking\Repository\BookingRepository;
use App\Booking\Repository\ClassSessionRepository;
use App\Shared\Enum\BookingStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/coach', name: 'api_coach_')]
#[IsGranted('ROLE_COACH')]
class ScheduleController extends AbstractController
{
    public function __construct(
        private readonly ClassSessionRepository $classSessionRepository,
        private readonly BookingRepository $bookingRepository
    ) {
    }

    #[Route('/schedule', name: 'schedule', methods: ['GET'])]
    public function getSchedule(#[CurrentUser] ?User $coach): JsonResponse
    {
        if (!$coach) {
            return $this->json(['error' => 'Authentication required.'], Response::HTTP_UNAUTHORIZED);
        }

        $classes = $this->classSessionRepository->findByCoach($coach);

        $data = array_map(function ($c) {
            return [
                'id' => $c->getId(),
                'name' => $c->getName(),
                'room' => $c->getRoom(),
                'dayOfWeek' => $c->getDayOfWeek(),
                'startTime' => $c->getStartTime()->format('H:i'),
                'endTime' => $c->getEndTime()->format('H:i'),
                'capacity' => $c->getCapacity(),
                'enrolledCount' => $c->getConfirmedBookingsCount(),
            ];
        }, $classes);

        return $this->json($data);
    }

    #[Route('/classes/{id}/members', name: 'class_members', methods: ['GET'])]
    public function getClassMembers(int $id, #[CurrentUser] ?User $coach): JsonResponse
    {
        if (!$coach) {
            return $this->json(['error' => 'Authentication required.'], Response::HTTP_UNAUTHORIZED);
        }

        $session = $this->classSessionRepository->find($id);
        if (!$session) {
            return $this->json(['error' => 'Class session not found.'], Response::HTTP_NOT_FOUND);
        }

        if ($session->getCoach() !== $coach) {
            return $this->json(['error' => 'You are not assigned to this class session.'], Response::HTTP_FORBIDDEN);
        }

        $confirmedBookings = $this->bookingRepository->findByClassSession($session, BookingStatus::CONFIRMED);

        $members = array_map(function ($b) {
            return [
                'bookingId' => $b->getId(),
                'memberId' => $b->getMember()->getId(),
                'fullName' => $b->getMember()->getFullName(),
                'email' => $b->getMember()->getEmail(),
                'bookedAt' => $b->getBookedAt()->format(\DateTimeInterface::ATOM),
            ];
        }, $confirmedBookings);

        return $this->json([
            'classId' => $session->getId(),
            'className' => $session->getName(),
            'room' => $session->getRoom(),
            'dayOfWeek' => $session->getDayOfWeek(),
            'time' => $session->getStartTime()->format('H:i') . ' - ' . $session->getEndTime()->format('H:i'),
            'capacity' => $session->getCapacity(),
            'enrolledCount' => count($members),
            'members' => $members,
        ]);
    }
}
