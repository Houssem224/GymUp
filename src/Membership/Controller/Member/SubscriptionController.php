<?php

namespace App\Membership\Controller\Member;

use App\Auth\Entity\User;
use App\Membership\Repository\PlanRepository;
use App\Membership\Repository\SubscriptionRepository;
use App\Membership\Service\MembershipService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/member/membership', name: 'api_member_membership_')]
#[IsGranted('ROLE_MEMBER')]
class SubscriptionController extends AbstractController
{
    public function __construct(
        private readonly MembershipService $membershipService,
        private readonly PlanRepository $planRepository,
        private readonly SubscriptionRepository $subscriptionRepository
    ) {
    }

    #[Route('/plans', name: 'available_plans', methods: ['GET'])]
    public function availablePlans(): JsonResponse
    {
        $plans = $this->planRepository->findActivePlans();

        $data = array_map(function ($p) {
            return [
                'id' => $p->getId(),
                'name' => $p->getName(),
                'price' => $p->getPrice(),
                'durationDays' => $p->getDurationDays(),
                'accessScope' => $p->getAccessScope()->value,
                'maxMembers' => $p->getMaxMembers(),
            ];
        }, $plans);

        return $this->json($data);
    }

    #[Route('/subscribe', name: 'subscribe', methods: ['POST'])]
    public function subscribe(#[CurrentUser] ?User $member, Request $request): JsonResponse
    {
        if (!$member) {
            return $this->json(['error' => 'Authentication required.'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        $planId = $data['planId'] ?? null;

        if (!$planId) {
            return $this->json(['error' => 'planId is required.'], Response::HTTP_BAD_REQUEST);
        }

        $plan = $this->planRepository->find($planId);
        if (!$plan) {
            return $this->json(['error' => 'Plan not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $subscription = $this->membershipService->subscribeMember($member, $plan);

            return $this->json([
                'message' => 'Subscription created successfully. Please complete payment to activate your membership.',
                'subscription' => [
                    'id' => $subscription->getId(),
                    'planId' => $plan->getId(),
                    'planName' => $plan->getName(),
                    'price' => $plan->getPrice(),
                    'status' => $subscription->getStatus()->value,
                ]
            ], Response::HTTP_CREATED);
        } catch (LogicException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/status', name: 'current_status', methods: ['GET'])]
    public function currentStatus(#[CurrentUser] ?User $member): JsonResponse
    {
        if (!$member) {
            return $this->json(['error' => 'Authentication required.'], Response::HTTP_UNAUTHORIZED);
        }

        $activeSubscription = $this->subscriptionRepository->findActiveSubscription($member);

        if (!$activeSubscription) {
            return $this->json([
                'hasActiveSubscription' => false,
                'message' => 'No active subscription found.',
            ]);
        }

        $today = new \DateTimeImmutable('today');
        $daysRemaining = max(0, (int)$today->diff($activeSubscription->getEndDate())->format('%r%a'));

        return $this->json([
            'hasActiveSubscription' => true,
            'subscriptionId' => $activeSubscription->getId(),
            'plan' => [
                'id' => $activeSubscription->getPlan()->getId(),
                'name' => $activeSubscription->getPlan()->getName(),
                'accessScope' => $activeSubscription->getPlan()->getAccessScope()->value,
            ],
            'status' => $activeSubscription->getStatus()->value,
            'startDate' => $activeSubscription->getStartDate()->format('Y-m-d'),
            'endDate' => $activeSubscription->getEndDate()->format('Y-m-d'),
            'daysRemaining' => $daysRemaining,
            'isExpiringSoon' => ($daysRemaining <= 5),
        ]);
    }

    #[Route('/renew', name: 'renew', methods: ['POST'])]
    public function renew(#[CurrentUser] ?User $member, Request $request): JsonResponse
    {
        if (!$member) {
            return $this->json(['error' => 'Authentication required.'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        $planId = $data['planId'] ?? null;

        if (!$planId) {
            return $this->json(['error' => 'planId is required.'], Response::HTTP_BAD_REQUEST);
        }

        $plan = $this->planRepository->find($planId);
        if (!$plan) {
            return $this->json(['error' => 'Plan not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $subscription = $this->membershipService->renewSubscription($member, $plan);

            return $this->json([
                'message' => 'Subscription renewal created successfully. Please complete payment to activate your membership.',
                'subscription' => [
                    'id' => $subscription->getId(),
                    'planId' => $plan->getId(),
                    'planName' => $plan->getName(),
                    'price' => $plan->getPrice(),
                    'status' => $subscription->getStatus()->value,
                ]
            ], Response::HTTP_CREATED);
        } catch (LogicException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/history', name: 'history', methods: ['GET'])]
    public function history(#[CurrentUser] ?User $member): JsonResponse
    {
        if (!$member) {
            return $this->json(['error' => 'Authentication required.'], Response::HTTP_UNAUTHORIZED);
        }

        $subscriptions = $this->subscriptionRepository->findByMember($member);

        $data = array_map(function ($s) {
            return [
                'id' => $s->getId(),
                'plan' => [
                    'id' => $s->getPlan()->getId(),
                    'name' => $s->getPlan()->getName(),
                    'price' => $s->getPlan()->getPrice(),
                    'accessScope' => $s->getPlan()->getAccessScope()->value,
                ],
                'status' => $s->getStatus()->value,
                'startDate' => $s->getStartDate()->format('Y-m-d'),
                'endDate' => $s->getEndDate()->format('Y-m-d'),
                'isActive' => $s->isActive(),
            ];
        }, $subscriptions);

        return $this->json($data);
    }
}
