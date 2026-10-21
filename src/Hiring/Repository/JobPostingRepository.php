<?php

namespace App\Hiring\Repository;

use App\Hiring\Entity\JobPosting;
use App\Shared\Enum\JobPostingStatus;
use App\Shared\Enum\PositionType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JobPosting>
 */
class JobPostingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobPosting::class);
    }

    public function save(JobPosting $jobPosting, bool $flush = true): void
    {
        $this->getEntityManager()->persist($jobPosting);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return JobPosting[]
     */
    public function findOpenPostings(?PositionType $positionType = null): array
    {
        $qb = $this->createQueryBuilder('jp')
            ->andWhere('jp.status = :status')
            ->setParameter('status', JobPostingStatus::OPEN)
            ->orderBy('jp.openedAt', 'DESC');

        if ($positionType !== null) {
            $qb->andWhere('jp.positionType = :positionType')
               ->setParameter('positionType', $positionType);
        }

        return $qb->getQuery()->getResult();
    }
}
