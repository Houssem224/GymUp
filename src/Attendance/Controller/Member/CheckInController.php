<?php

namespace App\Attendance\Controller\Member;

use App\Attendance\Repository\CheckInRepository;
use App\Attendance\Repository\StreakRepository;
use App\Attendance\Service\StreakService;
use App\Auth\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/attendance', name: 'api_attendance_')]
class CheckInController extends AbstractController
{
    public function __construct(
        private readonly StreakService $streakService,
        private readonly CheckInRepository $checkInRepository,
        private readonly StreakRepository $streakRepository
    ) {
    }

    #[Route('/check-in', name: 'check_in', methods: ['POST'])]
    public function checkIn(#[CurrentUser] ?User $user, Request $request): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$user->isActive()) {
            return $this->json(['error' => 'Account is inactive. Please contact gym staff.'], Response::HTTP_FORBIDDEN);
        }

        // Process QR attendance and streak calculation
        $result = $this->streakService->processCheckIn($user);

        return $this->json([
            'success' => $result->success,
            'message' => $result->message,
            'data' => [
                'checkInId' => $result->checkIn?->getId(),
                'scannedAt' => $result->checkIn?->getScannedAt()->format(\DateTimeInterface::ATOM),
                'currentStreak' => $result->currentStreak,
                'streakIncremented' => $result->streakIncremented,
                'streakBroken' => $result->streakBroken,
                'scheduleDays' => $result->activeScheduleDays,
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/history', name: 'history', methods: ['GET'])]
    public function history(#[CurrentUser] ?User $user, Request $request): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $limit = min(100, max(1, (int) $request->query->get('limit', 20)));
        $checkIns = $this->checkInRepository->findByMember($user, $limit);
        $totalCheckIns = $this->checkInRepository->countTotalForMember($user);
        $streak = $this->streakRepository->findByMember($user);
        $effectiveStreak = $this->streakService->getEffectiveStreak($user);

        $checkInList = array_map(function ($ci) {
            return [
                'id' => $ci->getId(),
                'date' => $ci->getCheckInDate()->format('Y-m-d'),
                'scannedAt' => $ci->getScannedAt()->format(\DateTimeInterface::ATOM),
            ];
        }, $checkIns);

        return $this->json([
            'totalAttendances' => $totalCheckIns,
            'currentStreak' => $effectiveStreak,
            'storedStreak' => $streak?->getCurrentStreak() ?? 0,
            'lastSuccessDate' => $streak?->getLastSuccessDate()?->format('Y-m-d'),
            'history' => $checkInList,
        ]);
    }
}
