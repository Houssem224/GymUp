<?php

namespace App\Booking\Repository;

use App\Auth\Entity\User;
use App\Booking\Entity\Booking;
use App\Booking\Entity\ClassSession;
use App\Shared\Enum\BookingStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Booking>
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    public function save(Booking $booking, bool $flush = true): void
    {
        $this->getEntityManager()->persist($booking);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findExistingBooking(User $member, ClassSession $classSession): ?Booking
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.member = :member')
            ->andWhere('b.classSession = :classSession')
            ->andWhere('b.status IN (:activeStatuses)')
            ->setParameter('member', $member)
            ->setParameter('classSession', $classSession)
            ->setParameter('activeStatuses', [BookingStatus::CONFIRMED, BookingStatus::WAITLIST])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Booking[]
     */
    public function findByMember(User $member): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.member = :member')
            ->setParameter('member', $member)
            ->orderBy('b.bookedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Booking[]
     */
    public function findByClassSession(ClassSession $classSession, ?BookingStatus $status = null): array
    {
        $qb = $this->createQueryBuilder('b')
            ->andWhere('b.classSession = :classSession')
            ->setParameter('classSession', $classSession)
            ->orderBy('b.bookedAt', 'ASC');

        if ($status !== null) {
            $qb->andWhere('b.status = :status')
               ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    public function countConfirmed(ClassSession $classSession): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->andWhere('b.classSession = :classSession')
            ->andWhere('b.status = :status')
            ->setParameter('classSession', $classSession)
            ->setParameter('status', BookingStatus::CONFIRMED)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Finds the next person in line on the waitlist for a class session.
     */
    public function findFirstWaitlisted(ClassSession $classSession): ?Booking
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.classSession = :classSession')
            ->andWhere('b.status = :status')
            ->setParameter('classSession', $classSession)
            ->setParameter('status', BookingStatus::WAITLIST)
            ->orderBy('b.bookedAt', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Checks if member already has a confirmed booking for an overlapping class session (Rule 13).
     * @return Booking[]
     */
    public function findOverlappingMemberBookings(
        User $member,
        string $dayOfWeek,
        \DateTimeInterface $startTime,
        \DateTimeInterface $endTime,
        ?int $excludeBookingId = null
    ): array {
        $qb = $this->createQueryBuilder('b')
            ->join('b.classSession', 'cs')
            ->andWhere('b.member = :member')
            ->andWhere('b.status = :status')
            ->andWhere('cs.dayOfWeek = :dayOfWeek')
            ->andWhere('cs.startTime < :endTime')
            ->andWhere('cs.endTime > :startTime')
            ->setParameter('member', $member)
            ->setParameter('status', BookingStatus::CONFIRMED)
            ->setParameter('dayOfWeek', strtoupper(trim($dayOfWeek)))
            ->setParameter('startTime', $startTime)
            ->setParameter('endTime', $endTime);

        if ($excludeBookingId !== null) {
            $qb->andWhere('b.id != :excludeId')
               ->setParameter('excludeId', $excludeBookingId);
        }

        return $qb->getQuery()->getResult();
    }
}
