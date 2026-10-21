<?php

namespace App\Hiring\Repository;

use App\Hiring\Entity\Application;
use App\Hiring\Entity\JobPosting;
use App\Shared\Enum\ApplicationStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Application>
 */
class ApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Application::class);
    }

    public function save(Application $application, bool $flush = true): void
    {
        $this->getEntityManager()->persist($application);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Application[]
     */
    public function findByPosting(JobPosting $jobPosting, ?ApplicationStatus $status = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.jobPosting = :jobPosting')
            ->setParameter('jobPosting', $jobPosting)
            ->orderBy('a.submittedAt', 'DESC');

        if ($status !== null) {
            $qb->andWhere('a.status = :status')
               ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Application[]
     */
    public function findPending(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.status = :status')
            ->setParameter('status', ApplicationStatus::PENDING)
            ->orderBy('a.submittedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
