<?php

namespace App\Payment\Service;

use App\Auth\Entity\User;
use App\Membership\Entity\Plan;
use App\Membership\Entity\Subscription;
use App\Payment\Entity\Payment;
use App\Payment\Repository\PaymentRepository;
use App\Shared\Enum\PaymentMethod;
use App\Shared\Enum\SubscriptionStatus;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use LogicException;

class PaymentService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PaymentRepository $paymentRepository
    ) {
    }

    /**
     * Process an online payment by a member for their subscription.
     */
    public function processOnlinePayment(User $member, Subscription $subscription, ?string $paymentReference = null): Payment
    {
        if ($subscription->getMember() !== $member) {
            throw new LogicException('This subscription does not belong to the current member.');
        }

        if ($subscription->getStatus() === SubscriptionStatus::ACTIVE) {
            throw new LogicException('This subscription is already active and paid for.');
        }

        $plan = $subscription->getPlan();
        if (!$plan || !$plan->isActive()) {
            throw new LogicException('The selected plan is no longer active.');
        }

        $amount = (float) $plan->getPrice();

        $payment = new Payment(
            member: $member,
            subscription: $subscription,
            amount: $amount,
            method: PaymentMethod::ONLINE,
            processedBy: null,
            paidAt: new \DateTimeImmutable()
        );

        // Activate the subscription
        $this->activateSubscription($subscription, $plan);

        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        return $payment;
    }

    /**
     * Process an in-person cash payment handled by a receptionist or admin.
     */
    public function processCashPayment(
        User $processedByStaff,
        User $member,
        Subscription $subscription,
        ?float $amountPaid = null
    ): Payment {
        $plan = $subscription->getPlan();
        if (!$plan) {
            throw new LogicException('Subscription has no associated plan.');
        }

        $expectedAmount = (float) $plan->getPrice();
        $amount = $amountPaid ?? $expectedAmount;

        if ($amount < $expectedAmount) {
            throw new InvalidArgumentException(
                "Payment amount (\${$amount}) is less than plan price (\${$expectedAmount})."
            );
        }

        $payment = new Payment(
            member: $member,
            subscription: $subscription,
            amount: $amount,
            method: PaymentMethod::CASH,
            processedBy: $processedByStaff,
            paidAt: new \DateTimeImmutable()
        );

        // Activate the subscription
        $this->activateSubscription($subscription, $plan);

        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        return $payment;
    }

    /**
     * Set subscription dates based on plan duration and mark ACTIVE.
     */
    private function activateSubscription(Subscription $subscription, Plan $plan): void
    {
        $startDate = new \DateTimeImmutable('today');
        $endDate = $startDate->modify('+' . $plan->getDurationDays() . ' days');

        $subscription->setStartDate($startDate);
        $subscription->setEndDate($endDate);
        $subscription->setStatus(SubscriptionStatus::ACTIVE);
    }

    /**
     * Retrieve revenue reports broken down by online and cash payments.
     */
    public function getRevenueReport(?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        return $this->paymentRepository->getRevenueSummary($startDate, $endDate);
    }
}
