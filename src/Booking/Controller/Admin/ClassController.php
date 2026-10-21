<?php

namespace App\Booking\Controller\Admin;

use App\Auth\Repository\UserRepository;
use App\Booking\Repository\BookingRepository;
use App\Booking\Repository\ClassSessionRepository;
use App\Booking\Service\BookingService;
use App\Shared\Enum\UserRole;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/classes', name: 'api_admin_classes_')]
#[IsGranted('ROLE_ADMIN')]
class ClassController extends AbstractController
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly ClassSessionRepository $classSessionRepository,
        private readonly BookingRepository $bookingRepository,
        private readonly UserRepository $userRepository
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $classes = $this->classSessionRepository->findAll();

        $data = array_map(function ($c) {
            return [
                'id' => $c->getId(),
                'name' => $c->getName(),
                'coach' => [
                    'id' => $c->getCoach()->getId(),
                    'fullName' => $c->getCoach()->getFullName() ?? $c->getCoach()->getEmail(),
                ],
                'room' => $c->getRoom(),
                'dayOfWeek' => $c->getDayOfWeek(),
                'startTime' => $c->getStartTime()->format('H:i'),
                'endTime' => $c->getEndTime()->format('H:i'),
                'capacity' => $c->getCapacity(),
                'confirmedCount' => $c->getConfirmedBookingsCount(),
                'isFull' => $c->isFull(),
            ];
        }, $classes);

        return $this->json($data);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $name = $data['name'] ?? null;
        $coachId = $data['coachId'] ?? null;
        $room = $data['room'] ?? null;
        $dayOfWeek = $data['dayOfWeek'] ?? null;
        $startTimeStr = $data['startTime'] ?? null;
        $endTimeStr = $data['endTime'] ?? null;
        $capacity = (int)($data['capacity'] ?? 0);

        if (!$name || !$coachId || !$room || !$dayOfWeek || !$startTimeStr || !$endTimeStr || $capacity <= 0) {
            return $this->json([
                'error' => 'All fields (name, coachId, room, dayOfWeek, startTime, endTime, capacity) are required.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $coach = $this->userRepository->find($coachId);
        if (!$coach || $coach->getRole() !== UserRole::COACH) {
            return $this->json(['error' => 'Assigned user must exist and have the COACH role.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $startTime = new \DateTimeImmutable($startTimeStr);
            $endTime = new \DateTimeImmutable($endTimeStr);

            $session = $this->bookingService->createClassSession(
                name: $name,
                coach: $coach,
                room: $room,
                dayOfWeek: $dayOfWeek,
                startTime: $startTime,
                endTime: $endTime,
                capacity: $capacity
            );

            return $this->json([
                'message' => 'Class session created successfully.',
                'class' => [
                    'id' => $session->getId(),
                    'name' => $session->getName(),
                    'coach' => $coach->getFullName() ?? $coach->getEmail(),
                    'room' => $session->getRoom(),
                    'dayOfWeek' => $session->getDayOfWeek(),
                    'startTime' => $session->getStartTime()->format('H:i'),
                    'endTime' => $session->getEndTime()->format('H:i'),
                    'capacity' => $session->getCapacity(),
                ]
            ], Response::HTTP_CREATED);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }
    }

    #[Route('/{id}/bookings', name: 'bookings', methods: ['GET'])]
    public function viewBookings(int $id): JsonResponse
    {
        $session = $this->classSessionRepository->find($id);
        if (!$session) {
            return $this->json(['error' => 'Class session not found.'], Response::HTTP_NOT_FOUND);
        }

        $bookings = $this->bookingRepository->findByClassSession($session);

        $data = array_map(function ($b) {
            return [
                'id' => $b->getId(),
                'member' => [
                    'id' => $b->getMember()->getId(),
                    'email' => $b->getMember()->getEmail(),
                    'fullName' => $b->getMember()->getFullName(),
                ],
                'status' => $b->getStatus()->value,
                'bookedAt' => $b->getBookedAt()->format(\DateTimeInterface::ATOM),
            ];
        }, $bookings);

        return $this->json([
            'classId' => $session->getId(),
            'className' => $session->getName(),
            'capacity' => $session->getCapacity(),
            'bookings' => $data,
        ]);
    }
}
