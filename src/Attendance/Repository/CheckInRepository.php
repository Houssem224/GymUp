<?php

namespace App\Attendance\Repository;

use App\Attendance\Entity\CheckIn;
use App\Auth\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CheckIn>
 */
class CheckInRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CheckIn::class);
    }

    public function save(CheckIn $checkIn, bool $flush = true): void
    {
        $this->getEntityManager()->persist($checkIn);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findTodayCheckIn(User $member, \DateTimeInterface $date): ?CheckIn
    {
        $dayStart = \DateTimeImmutable::createFromInterface($date)->setTime(0, 0, 0);
        $dayEnd = \DateTimeImmutable::createFromInterface($date)->setTime(23, 59, 59);

        return $this->createQueryBuilder('c')
            ->andWhere('c.member = :member')
            ->andWhere('c.scannedAt >= :dayStart')
            ->andWhere('c.scannedAt <= :dayEnd')
            ->setParameter('member', $member)
            ->setParameter('dayStart', $dayStart)
            ->setParameter('dayEnd', $dayEnd)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return CheckIn[]
     */
    public function findByMember(User $member, int $limit = 50): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.member = :member')
            ->setParameter('member', $member)
            ->orderBy('c.scannedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countTotalForMember(User $member): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.member = :member')
            ->setParameter('member', $member)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
