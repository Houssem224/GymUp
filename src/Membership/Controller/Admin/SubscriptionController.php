<?php

namespace App\Membership\Controller\Admin;

use App\Membership\Repository\SubscriptionRepository;
use App\Shared\Enum\SubscriptionStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/subscriptions', name: 'api_admin_subscriptions_')]
#[IsGranted('ROLE_ADMIN')]
class SubscriptionController extends AbstractController
{
    public function __construct(
        private readonly SubscriptionRepository $subscriptionRepository
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $statusParam = $request->query->get('status');
        $planId = $request->query->get('planId');
        $limit = min(100, max(1, (int) $request->query->get('limit', 50)));

        $qb = $this->subscriptionRepository->createQueryBuilder('s')
            ->join('s.member', 'm')
            ->join('s.plan', 'p')
            ->orderBy('s.startDate', 'DESC')
            ->setMaxResults($limit);

        if ($statusParam !== null) {
            $status = SubscriptionStatus::tryFrom(strtoupper($statusParam));
            if ($status) {
                $qb->andWhere('s.status = :status')
                   ->setParameter('status', $status);
            }
        }

        if ($planId !== null) {
            $qb->andWhere('p.id = :planId')
               ->setParameter('planId', (int) $planId);
        }

        $subscriptions = $qb->getQuery()->getResult();

        $data = array_map(function ($s) {
            return [
                'id' => $s->getId(),
                'member' => [
                    'id' => $s->getMember()->getId(),
                    'email' => $s->getMember()->getEmail(),
                    'fullName' => $s->getMember()->getFullName(),
                ],
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

    #[Route('/{id}', name: 'view', methods: ['GET'])]
    public function view(int $id): JsonResponse
    {
        $subscription = $this->subscriptionRepository->find($id);
        if (!$subscription) {
            return $this->json(['error' => 'Subscription not found.'], 404);
        }

        return $this->json([
            'id' => $subscription->getId(),
            'member' => [
                'id' => $subscription->getMember()->getId(),
                'email' => $subscription->getMember()->getEmail(),
                'fullName' => $subscription->getMember()->getFullName(),
            ],
            'plan' => [
                'id' => $subscription->getPlan()->getId(),
                'name' => $subscription->getPlan()->getName(),
                'price' => $subscription->getPlan()->getPrice(),
                'durationDays' => $subscription->getPlan()->getDurationDays(),
                'accessScope' => $subscription->getPlan()->getAccessScope()->value,
            ],
            'status' => $subscription->getStatus()->value,
            'startDate' => $subscription->getStartDate()->format('Y-m-d'),
            'endDate' => $subscription->getEndDate()->format('Y-m-d'),
            'isActive' => $subscription->isActive(),
            'payments' => array_map(function ($p) {
                return [
                    'id' => $p->getId(),
                    'amount' => $p->getAmount(),
                    'method' => $p->getMethod()->value,
                    'paidAt' => $p->getPaidAt()->format(\DateTimeInterface::ATOM),
                ];
            }, $subscription->getPayments()->toArray()),
        ]);
    }
}
