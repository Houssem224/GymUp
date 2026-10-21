<?php

namespace App\Attendance\Controller\Member;

use App\Attendance\Service\ScheduleService;
use App\Auth\Entity\User;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/attendance/schedule', name: 'api_attendance_schedule_')]
class ScheduleController extends AbstractController
{
    public function __construct(
        private readonly ScheduleService $scheduleService
    ) {
    }

    #[Route('', name: 'get', methods: ['GET'])]
    public function getSchedule(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $schedule = $this->scheduleService->getMemberSchedule($user);

        return $this->json([
            'memberId' => $user->getId(),
            'weekDays' => $schedule ? $schedule->getWeekDays() : [],
            'updatedAt' => $schedule?->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ]);
    }

    #[Route('', name: 'update', methods: ['PUT', 'POST'])]
    public function updateSchedule(#[CurrentUser] ?User $user, Request $request): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || !isset($data['weekDays']) || !is_array($data['weekDays'])) {
            return $this->json([
                'error' => 'Invalid payload. Expected JSON: { "weekDays": ["MONDAY", "WEDNESDAY", "FRIDAY"] }'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $schedule = $this->scheduleService->updateMemberSchedule($user, $data['weekDays']);

            return $this->json([
                'success' => true,
                'message' => 'Weekly schedule updated successfully.',
                'data' => [
                    'weekDays' => $schedule->getWeekDays(),
                    'updatedAt' => $schedule->getUpdatedAt()->format(\DateTimeInterface::ATOM),
                ]
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
