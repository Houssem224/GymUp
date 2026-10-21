<?php

namespace App\Attendance\Repository;

use App\Attendance\Entity\Streak;
use App\Auth\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Streak>
 */
class StreakRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Streak::class);
    }

    public function save(Streak $streak, bool $flush = true): void
    {
        $this->getEntityManager()->persist($streak);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByMember(User $member): ?Streak
    {
        return $this->findOneBy(['member' => $member]);
    }

    /**
     * @return Streak[]
     */
    public function findTopStreaks(int $limit = 10): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.currentStreak', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
