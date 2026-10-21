<?php

namespace App\Payment\Controller\Receptionist;

use App\Auth\Entity\User;
use App\Auth\Repository\UserRepository;
use App\Membership\Repository\SubscriptionRepository;
use App\Payment\Service\PaymentService;
use InvalidArgumentException;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/receptionist/payments', name: 'api_receptionist_payments_')]
#[IsGranted('ROLE_RECEPTIONIST')]
class CashPaymentController extends AbstractController
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly UserRepository $userRepository,
        private readonly SubscriptionRepository $subscriptionRepository
    ) {
    }

    #[Route('/cash', name: 'record_cash', methods: ['POST'])]
    public function recordCashPayment(#[CurrentUser] ?User $staffUser, Request $request): JsonResponse
    {
        if (!$staffUser) {
            return $this->json(['error' => 'Authentication required.'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        $memberId = $data['memberId'] ?? null;
        $subscriptionId = $data['subscriptionId'] ?? null;
        $amountPaid = isset($data['amount']) ? (float)$data['amount'] : null;

        if (!$memberId || !$subscriptionId) {
            return $this->json([
                'error' => 'memberId and subscriptionId are required.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $member = $this->userRepository->find($memberId);
        if (!$member) {
            return $this->json(['error' => 'Member not found.'], Response::HTTP_NOT_FOUND);
        }

        $subscription = $this->subscriptionRepository->find($subscriptionId);
        if (!$subscription) {
            return $this->json(['error' => 'Subscription not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $payment = $this->paymentService->processCashPayment(
                processedByStaff: $staffUser,
                member: $member,
                subscription: $subscription,
                amountPaid: $amountPaid
            );

            return $this->json([
                'message' => 'Cash payment recorded and subscription activated successfully.',
                'payment' => [
                    'id' => $payment->getId(),
                    'amount' => $payment->getAmount(),
                    'method' => $payment->getMethod()->value,
                    'processedBy' => $staffUser->getEmail(),
                    'paidAt' => $payment->getPaidAt()->format(\DateTimeInterface::ATOM),
                ],
                'subscription' => [
                    'id' => $subscription->getId(),
                    'plan' => $subscription->getPlan()?->getName(),
                    'status' => $subscription->getStatus()->value,
                    'startDate' => $subscription->getStartDate()->format('Y-m-d'),
                    'endDate' => $subscription->getEndDate()->format('Y-m-d'),
                ]
            ], Response::HTTP_CREATED);
        } catch (InvalidArgumentException|LogicException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
