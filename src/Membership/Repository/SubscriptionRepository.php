<?php

namespace App\Membership\Repository;

use App\Auth\Entity\User;
use App\Membership\Entity\Subscription;
use App\Shared\Enum\SubscriptionStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subscription>
 */
class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }

    public function save(Subscription $subscription, bool $flush = true): void
    {
        $this->getEntityManager()->persist($subscription);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findActiveSubscription(User $member): ?Subscription
    {
        $today = new \DateTimeImmutable('today');

        return $this->createQueryBuilder('s')
            ->andWhere('s.member = :member')
            ->andWhere('s.status = :status')
            ->andWhere('s.startDate <= :today')
            ->andWhere('s.endDate >= :today')
            ->setParameter('member', $member)
            ->setParameter('status', SubscriptionStatus::ACTIVE)
            ->setParameter('today', $today)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
