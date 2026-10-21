<?php

namespace App\Membership\Controller\Receptionist;

use App\Membership\Repository\PlanRepository;
use App\Membership\Service\MembershipService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/receptionist', name: 'api_receptionist_')]
#[IsGranted('ROLE_RECEPTIONIST')]
class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly MembershipService $membershipService,
        private readonly PlanRepository $planRepository
    ) {
    }

    #[Route('/plans', name: 'running_plans', methods: ['GET'])]
    public function getRunningPlans(): JsonResponse
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

    #[Route('/members/register', name: 'register_walk_in', methods: ['POST'])]
    public function registerWalkIn(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? null;
        $fullName = $data['fullName'] ?? null;
        $planId = $data['planId'] ?? null;

        if (!$email || !$fullName || !$planId) {
            return $this->json([
                'error' => 'email, fullName, and planId are required.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $plan = $this->planRepository->find($planId);
        if (!$plan) {
            return $this->json(['error' => 'Membership plan not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $result = $this->membershipService->registerWalkInMember(
                email: $email,
                fullName: $fullName,
                plan: $plan
            );

            $member = $result['member'];
            $subscription = $result['subscription'];

            return $this->json([
                'message' => 'Walk-in member registered and enrolled in plan successfully.',
                'member' => [
                    'id' => $member->getId(),
                    'email' => $member->getEmail(),
                    'fullName' => $member->getFullName(),
                ],
                'subscription' => [
                    'id' => $subscription->getId(),
                    'planId' => $plan->getId(),
                    'planName' => $plan->getName(),
                    'status' => $subscription->getStatus()->value,
                    'price' => $plan->getPrice(),
                ],
                'temporaryPassword' => $result['temporaryPassword'],
            ], Response::HTTP_CREATED);
        } catch (LogicException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
