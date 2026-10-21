<?php

namespace App\Payment\Repository;

use App\Auth\Entity\User;
use App\Membership\Entity\Subscription;
use App\Payment\Entity\Payment;
use App\Shared\Enum\PaymentMethod;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Payment>
 */
class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    public function save(Payment $payment, bool $flush = true): void
    {
        $this->getEntityManager()->persist($payment);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Payment[]
     */
    public function findByMember(User $member): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.member = :member')
            ->setParameter('member', $member)
            ->orderBy('p.paidAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Payment[]
     */
    public function findBySubscription(Subscription $subscription): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.subscription = :subscription')
            ->setParameter('subscription', $subscription)
            ->orderBy('p.paidAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calculates total revenue broken down by payment method (online vs cash).
     */
    public function getRevenueSummary(?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p.method, SUM(p.amount) as totalAmount, COUNT(p.id) as paymentCount')
            ->groupBy('p.method');

        if ($startDate !== null) {
            $qb->andWhere('p.paidAt >= :startDate')
               ->setParameter('startDate', $startDate);
        }

        if ($endDate !== null) {
            $qb->andWhere('p.paidAt <= :endDate')
               ->setParameter('endDate', $endDate);
        }

        $results = $qb->getQuery()->getResult();

        $summary = [
            'onlineRevenue' => 0.0,
            'cashRevenue' => 0.0,
            'totalRevenue' => 0.0,
            'onlineCount' => 0,
            'cashCount' => 0,
            'totalCount' => 0,
        ];

        foreach ($results as $row) {
            $amount = (float) $row['totalAmount'];
            $count = (int) $row['paymentCount'];
            $method = $row['method'] instanceof PaymentMethod ? $row['method'] : PaymentMethod::from($row['method']);

            if ($method === PaymentMethod::ONLINE) {
                $summary['onlineRevenue'] = $amount;
                $summary['onlineCount'] = $count;
            } elseif ($method === PaymentMethod::CASH) {
                $summary['cashRevenue'] = $amount;
                $summary['cashCount'] = $count;
            }
        }

        $summary['totalRevenue'] = $summary['onlineRevenue'] + $summary['cashRevenue'];
        $summary['totalCount'] = $summary['onlineCount'] + $summary['cashCount'];

        return $summary;
    }

    /**
     * @return Payment[]
     */
    public function findRecent(int $limit = 50): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.paidAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
