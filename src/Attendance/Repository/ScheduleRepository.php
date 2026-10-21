<?php

namespace App\Attendance\Repository;

use App\Attendance\Entity\Schedule;
use App\Auth\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Schedule>
 */
class ScheduleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Schedule::class);
    }

    public function save(Schedule $schedule, bool $flush = true): void
    {
        $this->getEntityManager()->persist($schedule);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByMember(User $member): ?Schedule
    {
        return $this->findOneBy(['member' => $member]);
    }
}
