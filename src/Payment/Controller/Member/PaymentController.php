<?php

namespace App\Payment\Controller\Member;

use App\Auth\Entity\User;
use App\Membership\Repository\SubscriptionRepository;
use App\Payment\Repository\PaymentRepository;
use App\Payment\Service\PaymentService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/member/payments', name: 'api_member_payments_')]
#[IsGranted('ROLE_MEMBER')]
class PaymentController extends AbstractController
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly PaymentRepository $paymentRepository,
        private readonly SubscriptionRepository $subscriptionRepository
    ) {
    }

    #[Route('/checkout', name: 'checkout', methods: ['POST'])]
    public function checkout(#[CurrentUser] ?User $user, Request $request): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'Authentication required.'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        $subscriptionId = $data['subscriptionId'] ?? null;

        if (!$subscriptionId) {
            return $this->json(['error' => 'subscriptionId is required.'], Response::HTTP_BAD_REQUEST);
        }

        $subscription = $this->subscriptionRepository->find($subscriptionId);
        if (!$subscription) {
            return $this->json(['error' => 'Subscription not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $payment = $this->paymentService->processOnlinePayment($user, $subscription);

            return $this->json([
                'message' => 'Online payment successful. Subscription activated!',
                'payment' => [
                    'id' => $payment->getId(),
                    'amount' => $payment->getAmount(),
                    'method' => $payment->getMethod()->value,
                    'paidAt' => $payment->getPaidAt()->format(\DateTimeInterface::ATOM),
                ],
                'subscription' => [
                    'id' => $subscription->getId(),
                    'plan' => $subscription->getPlan()->getName(),
                    'status' => $subscription->getStatus()->value,
                    'startDate' => $subscription->getStartDate()->format('Y-m-d'),
                    'endDate' => $subscription->getEndDate()->format('Y-m-d'),
                ]
            ], Response::HTTP_CREATED);
        } catch (LogicException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/history', name: 'history', methods: ['GET'])]
    public function history(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'Authentication required.'], Response::HTTP_UNAUTHORIZED);
        }

        $payments = $this->paymentRepository->findByMember($user);

        $data = array_map(function ($p) {
            return [
                'id' => $p->getId(),
                'amount' => $p->getAmount(),
                'method' => $p->getMethod()->value,
                'planName' => $p->getSubscription()->getPlan()?->getName(),
                'paidAt' => $p->getPaidAt()->format(\DateTimeInterface::ATOM),
            ];
        }, $payments);

        return $this->json($data);
    }
}
