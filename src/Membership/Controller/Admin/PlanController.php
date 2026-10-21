<?php

namespace App\Membership\Controller\Admin;

use App\Membership\Repository\PlanRepository;
use App\Membership\Repository\SubscriptionRepository;
use App\Membership\Service\MembershipService;
use App\Shared\Enum\PlanAccessScope;
use App\Shared\Enum\SubscriptionStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/plans', name: 'api_admin_plans_')]
#[IsGranted('ROLE_ADMIN')]
class PlanController extends AbstractController
{
    public function __construct(
        private readonly MembershipService $membershipService,
        private readonly PlanRepository $planRepository,
        private readonly SubscriptionRepository $subscriptionRepository
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $plans = $this->planRepository->findAll();

        $data = array_map(function ($p) {
            return [
                'id' => $p->getId(),
                'name' => $p->getName(),
                'price' => $p->getPrice(),
                'durationDays' => $p->getDurationDays(),
                'accessScope' => $p->getAccessScope()->value,
                'maxMembers' => $p->getMaxMembers(),
                'isActive' => $p->isActive(),
            ];
        }, $plans);

        return $this->json($data);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $name = $data['name'] ?? null;
        $price = $data['price'] ?? null;
        $durationDays = (int)($data['durationDays'] ?? 0);
        $accessScopeStr = $data['accessScope'] ?? 'BOTH';
        $maxMembers = isset($data['maxMembers']) ? (int)$data['maxMembers'] : null;

        if (!$name || $price === null || $durationDays <= 0) {
            return $this->json([
                'error' => 'name, price, and positive durationDays are required.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $accessScope = PlanAccessScope::tryFrom(strtoupper($accessScopeStr)) ?? PlanAccessScope::BOTH;

        $plan = $this->membershipService->createPlan($name, $price, $durationDays, $accessScope, $maxMembers);

        return $this->json([
            'message' => 'Plan created successfully.',
            'plan' => [
                'id' => $plan->getId(),
                'name' => $plan->getName(),
                'price' => $plan->getPrice(),
                'durationDays' => $plan->getDurationDays(),
                'accessScope' => $plan->getAccessScope()->value,
                'maxMembers' => $plan->getMaxMembers(),
                'isActive' => $plan->isActive(),
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}/deactivate', name: 'deactivate', methods: ['PATCH', 'POST'])]
    public function deactivate(int $id): JsonResponse
    {
        $plan = $this->planRepository->find($id);
        if (!$plan) {
            return $this->json(['error' => 'Plan not found.'], Response::HTTP_NOT_FOUND);
        }

        $this->membershipService->deactivatePlan($plan);

        return $this->json(['message' => 'Plan deactivated successfully.']);
    }

    #[Route('/{id}/activate', name: 'activate', methods: ['PATCH', 'POST'])]
    public function activate(int $id): JsonResponse
    {
        $plan = $this->planRepository->find($id);
        if (!$plan) {
            return $this->json(['error' => 'Plan not found.'], Response::HTTP_NOT_FOUND);
        }

        $this->membershipService->activatePlan($plan);

        return $this->json(['message' => 'Plan activated successfully.']);
    }

    #[Route('/{id}', name: 'update', methods: ['PATCH', 'PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $plan = $this->planRepository->find($id);
        if (!$plan) {
            return $this->json(['error' => 'Plan not found.'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        $name = $data['name'] ?? null;
        $price = $data['price'] ?? null;
        $durationDays = isset($data['durationDays']) ? (int)$data['durationDays'] : null;
        $accessScopeStr = $data['accessScope'] ?? null;
        $maxMembers = isset($data['maxMembers']) ? (int)$data['maxMembers'] : null;
        $isActive = isset($data['isActive']) ? (bool)$data['isActive'] : null;

        if ($name !== null) {
            $plan->setName($name);
        }

        if ($price !== null) {
            $plan->setPrice($price);
        }

        if ($durationDays !== null) {
            if ($durationDays <= 0) {
                return $this->json(['error' => 'durationDays must be greater than 0.'], Response::HTTP_BAD_REQUEST);
            }
            $plan->setDurationDays($durationDays);
        }

        if ($accessScopeStr !== null) {
            $accessScope = PlanAccessScope::tryFrom(strtoupper($accessScopeStr));
            if (!$accessScope) {
                return $this->json(['error' => 'Invalid accessScope. Must be OPEN_GYM, CLASSES, or BOTH.'], Response::HTTP_BAD_REQUEST);
            }
            $plan->setAccessScope($accessScope);
        }

        if ($maxMembers !== null) {
            if ($maxMembers < 0) {
                return $this->json(['error' => 'maxMembers cannot be negative.'], Response::HTTP_BAD_REQUEST);
            }
            $plan->setMaxMembers($maxMembers === 0 ? null : $maxMembers);
        }

        if ($isActive !== null) {
            $plan->setIsActive($isActive);
        }

        $this->planRepository->save($plan);

        return $this->json([
            'message' => 'Plan updated successfully.',
            'plan' => [
                'id' => $plan->getId(),
                'name' => $plan->getName(),
                'price' => $plan->getPrice(),
                'durationDays' => $plan->getDurationDays(),
                'accessScope' => $plan->getAccessScope()->value,
                'maxMembers' => $plan->getMaxMembers(),
                'isActive' => $plan->isActive(),
            ]
        ]);
    }

    #[Route('/expiring', name: 'expiring', methods: ['GET'])]
    public function expiringSubscriptions(Request $request): JsonResponse
    {
        $days = (int) $request->query->get('days', 7);
        $expiring = $this->membershipService->getExpiringSubscriptions($days);

        $data = array_map(function ($s) {
            return [
                'subscriptionId' => $s->getId(),
                'member' => [
                    'id' => $s->getMember()->getId(),
                    'email' => $s->getMember()->getEmail(),
                    'fullName' => $s->getMember()->getFullName(),
                ],
                'planName' => $s->getPlan()->getName(),
                'endDate' => $s->getEndDate()->format('Y-m-d'),
                'daysRemaining' => (new \DateTimeImmutable('today'))->diff($s->getEndDate())->days,
            ];
        }, $expiring);

        return $this->json($data);
    }
}
