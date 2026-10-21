<?php

namespace App\Membership\Service;

use App\Auth\Entity\User;
use App\Auth\Repository\UserRepository;
use App\Membership\Entity\Plan;
use App\Membership\Entity\Subscription;
use App\Membership\Repository\PlanRepository;
use App\Membership\Repository\SubscriptionRepository;
use App\Shared\Enum\PlanAccessScope;
use App\Shared\Enum\SubscriptionStatus;
use App\Shared\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class MembershipService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlanRepository $planRepository,
        private readonly SubscriptionRepository $subscriptionRepository,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function createPlan(
        string $name,
        string|float $price,
        int $durationDays,
        PlanAccessScope $accessScope = PlanAccessScope::BOTH,
        ?int $maxMembers = null
    ): Plan {
        $plan = new Plan();
        $plan->setName($name)
            ->setPrice($price)
            ->setDurationDays($durationDays)
            ->setAccessScope($accessScope)
            ->setMaxMembers($maxMembers)
            ->setIsActive(true);

        $this->planRepository->save($plan);

        return $plan;
    }

    public function deactivatePlan(Plan $plan): void
    {
        $plan->setIsActive(false);
        $this->planRepository->save($plan);
    }

    public function activatePlan(Plan $plan): void
    {
        $plan->setIsActive(true);
        $this->planRepository->save($plan);
    }

    /**
     * Subscribe a member to a plan enforcing Rule 10 (Member cap limit).
     */
    public function subscribeMember(User $member, Plan $plan): Subscription
    {
        if (!$plan->isActive()) {
            throw new LogicException('This membership plan is currently deactivated.');
        }

        // Rule 10: Check maximum subscriber cap
        if ($plan->getMaxMembers() !== null) {
            $activeSubscribersCount = $this->subscriptionRepository->count([
                'plan' => $plan,
                'status' => SubscriptionStatus::ACTIVE,
            ]);

            if ($activeSubscribersCount >= $plan->getMaxMembers()) {
                throw new LogicException("Enrollment limit reached (Rule 10): Plan '{$plan->getName()}' has reached its maximum capacity of {$plan->getMaxMembers()} members.");
            }
        }

        $subscription = new Subscription();
        $subscription->setMember($member);
        $subscription->setPlan($plan);
        $subscription->setStatus(SubscriptionStatus::PENDING);

        $this->subscriptionRepository->save($subscription);

        return $subscription;
    }

    /**
     * Receptionist walk-in member registration and plan enrollment.
     */
    public function registerWalkInMember(
        string $email,
        string $fullName,
        Plan $plan,
        ?string $temporaryPassword = null
    ): array {
        $existing = $this->userRepository->findOneBy(['email' => strtolower(trim($email))]);
        if ($existing) {
            $member = $existing;
        } else {
            $tempPass = $temporaryPassword ?? bin2hex(random_bytes(4)) . '!Pass1';
            $member = new User();
            $member->setEmail(strtolower(trim($email)));
            $member->setFullName(trim($fullName));
            $member->setRole(UserRole::MEMBER);
            $member->setStatus('ACTIVE');
            $member->setPassword($this->passwordHasher->hashPassword($member, $tempPass));

            $this->userRepository->save($member);
        }

        $subscription = $this->subscribeMember($member, $plan);

        return [
            'member' => $member,
            'subscription' => $subscription,
            'temporaryPassword' => $existing ? null : ($tempPass ?? null),
        ];
    }

    /**
     * Fetch expiring memberships (within $daysThreshold days).
     * @return Subscription[]
     */
    public function getExpiringSubscriptions(int $daysThreshold = 7): array
    {
        $today = new \DateTimeImmutable('today');
        $expiryLimit = $today->modify("+{$daysThreshold} days");

        return $this->subscriptionRepository->createQueryBuilder('s')
            ->andWhere('s.status = :status')
            ->andWhere('s.endDate >= :today')
            ->andWhere('s.endDate <= :limit')
            ->setParameter('status', SubscriptionStatus::ACTIVE)
            ->setParameter('today', $today)
            ->setParameter('limit', $expiryLimit)
            ->orderBy('s.endDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Renew an existing subscription for a member.
     */
    public function renewSubscription(User $member, Plan $plan): Subscription
    {
        if (!$plan->isActive()) {
            throw new LogicException('This membership plan is currently deactivated.');
        }

        // Check if member has an existing subscription
        $existingSubscription = $this->subscriptionRepository->findActiveSubscription($member);

        // Rule 10: Check maximum subscriber cap
        if ($plan->getMaxMembers() !== null) {
            $activeSubscribersCount = $this->subscriptionRepository->count([
                'plan' => $plan,
                'status' => SubscriptionStatus::ACTIVE,
            ]);

            // If renewing with same plan, don't count the current subscription
            if ($existingSubscription && $existingSubscription->getPlan()->getId() === $plan->getId()) {
                $activeSubscribersCount--;
            }

            if ($activeSubscribersCount >= $plan->getMaxMembers()) {
                throw new LogicException("Enrollment limit reached (Rule 10): Plan '{$plan->getName()}' has reached its maximum capacity of {$plan->getMaxMembers()} members.");
            }
        }

        $subscription = new Subscription();
        $subscription->setMember($member);
        $subscription->setPlan($plan);
        $subscription->setStatus(SubscriptionStatus::PENDING);

        $this->subscriptionRepository->save($subscription);

        return $subscription;
    }
}
