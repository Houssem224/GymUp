<?php

namespace App\Membership\Controller\Admin;

use App\Membership\Repository\PlanRepository;
use App\Membership\Service\MembershipService;
use App\Shared\Enum\PlanAccessScope;
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
        private readonly PlanRepository $planRepository
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
