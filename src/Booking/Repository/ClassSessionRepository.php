<?php

namespace App\Booking\Repository;

use App\Auth\Entity\User;
use App\Booking\Entity\ClassSession;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClassSession>
 */
class ClassSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClassSession::class);
    }

    public function save(ClassSession $classSession, bool $flush = true): void
    {
        $this->getEntityManager()->persist($classSession);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return ClassSession[]
     */
    public function findByCoach(User $coach): array
    {
        return $this->createQueryBuilder('cs')
            ->andWhere('cs.coach = :coach')
            ->setParameter('coach', $coach)
            ->orderBy('cs.dayOfWeek', 'ASC')
            ->addOrderBy('cs.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Finds any session where the same coach has an overlapping time slot on the same day.
     * @return ClassSession[]
     */
    public function findOverlappingCoachSessions(
        User $coach,
        string $dayOfWeek,
        \DateTimeInterface $startTime,
        \DateTimeInterface $endTime,
        ?int $excludeSessionId = null
    ): array {
        $qb = $this->createQueryBuilder('cs')
            ->andWhere('cs.coach = :coach')
            ->andWhere('cs.dayOfWeek = :dayOfWeek')
            ->andWhere('cs.startTime < :endTime')
            ->andWhere('cs.endTime > :startTime')
            ->setParameter('coach', $coach)
            ->setParameter('dayOfWeek', strtoupper(trim($dayOfWeek)))
            ->setParameter('startTime', $startTime)
            ->setParameter('endTime', $endTime);

        if ($excludeSessionId !== null) {
            $qb->andWhere('cs.id != :excludeId')
               ->setParameter('excludeId', $excludeSessionId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Finds any session where the same room has an overlapping time slot on the same day.
     * @return ClassSession[]
     */
    public function findOverlappingRoomSessions(
        string $room,
        string $dayOfWeek,
        \DateTimeInterface $startTime,
        \DateTimeInterface $endTime,
        ?int $excludeSessionId = null
    ): array {
        $qb = $this->createQueryBuilder('cs')
            ->andWhere('LOWER(cs.room) = LOWER(:room)')
            ->andWhere('cs.dayOfWeek = :dayOfWeek')
            ->andWhere('cs.startTime < :endTime')
            ->andWhere('cs.endTime > :startTime')
            ->setParameter('room', trim($room))
            ->setParameter('dayOfWeek', strtoupper(trim($dayOfWeek)))
            ->setParameter('startTime', $startTime)
            ->setParameter('endTime', $endTime);

        if ($excludeSessionId !== null) {
            $qb->andWhere('cs.id != :excludeId')
               ->setParameter('excludeId', $excludeSessionId);
        }

        return $qb->getQuery()->getResult();
    }
}
