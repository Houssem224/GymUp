<?php

namespace App\Admin\Service;

use App\Attendance\Repository\StreakRepository;
use App\Auth\Repository\UserRepository;
use App\Booking\Repository\ClassSessionRepository;
use App\Membership\Repository\SubscriptionRepository;
use App\Membership\Service\MembershipService;
use App\Payment\Service\PaymentService;
use App\Shared\Enum\SubscriptionStatus;
use App\Shared\Enum\UserRole;

class DashboardAggregationService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly SubscriptionRepository $subscriptionRepository,
        private readonly ClassSessionRepository $classSessionRepository,
        private readonly StreakRepository $streakRepository,
        private readonly MembershipService $membershipService,
        private readonly PaymentService $paymentService
    ) {
    }

    /**
     * Aggregates key metrics for the Admin Dashboard:
     * - Active members count
     * - Class occupancy rates
     * - Monthly revenue (online vs cash)
     * - Expiring memberships
     * - Member streak activity
     */
    public function getDashboardMetrics(): array
    {
        // 1. Active members count
        $activeMembersCount = $this->subscriptionRepository->count([
            'status' => SubscriptionStatus::ACTIVE,
        ]);

        $totalMembersCount = $this->userRepository->count([
            'role' => UserRole::MEMBER,
        ]);

        // 2. Class occupancy rates
        $classSessions = $this->classSessionRepository->findAll();
        $totalCapacity = 0;
        $totalConfirmed = 0;
        $classDetails = [];

        foreach ($classSessions as $cs) {
            $confirmed = $cs->getConfirmedBookingsCount();
            $cap = $cs->getCapacity();
            $totalCapacity += $cap;
            $totalConfirmed += $confirmed;

            $rate = ($cap > 0) ? round(($confirmed / $cap) * 100, 1) : 0;
            $classDetails[] = [
                'id' => $cs->getId(),
                'name' => $cs->getName(),
                'room' => $cs->getRoom(),
                'dayOfWeek' => $cs->getDayOfWeek(),
                'capacity' => $cap,
                'confirmed' => $confirmed,
                'occupancyRatePercent' => $rate,
            ];
        }

        $overallOccupancyRate = ($totalCapacity > 0)
            ? round(($totalConfirmed / $totalCapacity) * 100, 1)
            : 0.0;

        // 3. Monthly revenue
        $firstDayOfMonth = new \DateTimeImmutable('first day of this month 00:00:00');
        $monthlyRevenue = $this->paymentService->getRevenueReport($firstDayOfMonth);

        // 4. Expiring memberships
        $expiringSubscriptions = $this->membershipService->getExpiringSubscriptions(7);
        $expiringCount = count($expiringSubscriptions);

        // 5. Member streak activity
        $topStreaks = $this->streakRepository->findTopStreaks(5);
        $streakLeaderboard = array_map(function ($s) {
            return [
                'memberId' => $s->getMember()->getId(),
                'memberName' => $s->getMember()->getFullName() ?? $s->getMember()->getEmail(),
                'streak' => $s->getCurrentStreak(),
                'lastSuccessDate' => $s->getLastSuccessDate()?->format('Y-m-d'),
            ];
        }, $topStreaks);

        return [
            'activeMembersCount' => $activeMembersCount,
            'totalRegisteredMembers' => $totalMembersCount,
            'overallOccupancyRatePercent' => $overallOccupancyRate,
            'classes' => $classDetails,
            'revenueThisMonth' => $monthlyRevenue,
            'expiringMembershipsCount' => $expiringCount,
            'topStreaks' => $streakLeaderboard,
        ];
    }
}
