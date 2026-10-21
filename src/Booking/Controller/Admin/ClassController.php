<?php

namespace App\Booking\Controller\Admin;

use App\Auth\Repository\UserRepository;
use App\Booking\Repository\BookingRepository;
use App\Booking\Repository\ClassSessionRepository;
use App\Booking\Service\BookingService;
use App\Shared\Enum\BookingStatus;
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

    #[Route('/{id}/waitlist', name: 'waitlist', methods: ['GET'])]
    public function viewWaitlist(int $id): JsonResponse
    {
        $session = $this->classSessionRepository->find($id);
        if (!$session) {
            return $this->json(['error' => 'Class session not found.'], Response::HTTP_NOT_FOUND);
        }

        $waitlistBookings = $this->bookingRepository->findByClassSession($session, BookingStatus::WAITLIST);

        $data = array_map(function ($b) {
            return [
                'id' => $b->getId(),
                'member' => [
                    'id' => $b->getMember()->getId(),
                    'email' => $b->getMember()->getEmail(),
                    'fullName' => $b->getMember()->getFullName(),
                ],
                'bookedAt' => $b->getBookedAt()->format(\DateTimeInterface::ATOM),
                'position' => 0, // Would need to calculate position based on order
            ];
        }, $waitlistBookings);

        // Calculate positions
        foreach ($data as $index => $item) {
            $data[$index]['position'] = $index + 1;
        }

        return $this->json([
            'classId' => $session->getId(),
            'className' => $session->getName(),
            'capacity' => $session->getCapacity(),
            'confirmedCount' => $session->getConfirmedBookingsCount(),
            'waitlistCount' => count($data),
            'waitlist' => $data,
        ]);
    }

    #[Route('/{id}', name: 'update', methods: ['PATCH', 'PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $session = $this->classSessionRepository->find($id);
        if (!$session) {
            return $this->json(['error' => 'Class session not found.'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        $name = $data['name'] ?? null;
        $coachId = $data['coachId'] ?? null;
        $room = $data['room'] ?? null;
        $dayOfWeek = $data['dayOfWeek'] ?? null;
        $startTimeStr = $data['startTime'] ?? null;
        $endTimeStr = $data['endTime'] ?? null;
        $capacity = isset($data['capacity']) ? (int)$data['capacity'] : null;

        if ($name !== null) {
            $session->setName($name);
        }

        if ($coachId !== null) {
            $coach = $this->userRepository->find($coachId);
            if (!$coach || $coach->getRole() !== UserRole::COACH) {
                return $this->json(['error' => 'Assigned user must exist and have the COACH role.'], Response::HTTP_BAD_REQUEST);
            }
            $session->setCoach($coach);
        }

        if ($room !== null) {
            $session->setRoom($room);
        }

        if ($dayOfWeek !== null) {
            $session->setDayOfWeek($dayOfWeek);
        }

        if ($startTimeStr !== null && $endTimeStr !== null) {
            try {
                $startTime = new \DateTimeImmutable($startTimeStr);
                $endTime = new \DateTimeImmutable($endTimeStr);

                if ($startTime >= $endTime) {
                    return $this->json(['error' => 'Start time must be before end time.'], Response::HTTP_BAD_REQUEST);
                }

                // Validate conflicts with new time
                $this->bookingService->validateClassSessionUpdate(
                    $session,
                    $session->getCoach(),
                    $room ?? $session->getRoom(),
                    $dayOfWeek ?? $session->getDayOfWeek(),
                    $startTime,
                    $endTime,
                    $id
                );

                $session->setStartTime($startTime);
                $session->setEndTime($endTime);
            } catch (InvalidArgumentException $e) {
                return $this->json(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
            }
        }

        if ($capacity !== null) {
            if ($capacity <= 0) {
                return $this->json(['error' => 'Capacity must be greater than 0.'], Response::HTTP_BAD_REQUEST);
            }
            $session->setCapacity($capacity);
        }

        $this->classSessionRepository->save($session);

        return $this->json([
            'message' => 'Class session updated successfully.',
            'class' => [
                'id' => $session->getId(),
                'name' => $session->getName(),
                'coach' => $session->getCoach()->getFullName() ?? $session->getCoach()->getEmail(),
                'room' => $session->getRoom(),
                'dayOfWeek' => $session->getDayOfWeek(),
                'startTime' => $session->getStartTime()->format('H:i'),
                'endTime' => $session->getEndTime()->format('H:i'),
                'capacity' => $session->getCapacity(),
            ]
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $session = $this->classSessionRepository->find($id);
        if (!$session) {
            return $this->json(['error' => 'Class session not found.'], Response::HTTP_NOT_FOUND);
        }

        $confirmedBookingsCount = $session->getConfirmedBookingsCount();
        $waitlistBookingsCount = count($this->bookingRepository->findByClassSession($session, BookingStatus::WAITLIST));

        if ($confirmedBookingsCount > 0 || $waitlistBookingsCount > 0) {
            return $this->json([
                'error' => "Cannot delete class session with {$confirmedBookingsCount} confirmed bookings and {$waitlistBookingsCount} waitlist entries. Cancel bookings first."
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->classSessionRepository->remove($session, true);

        return $this->json(['message' => 'Class session deleted successfully.']);
    }
}
