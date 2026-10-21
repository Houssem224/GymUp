<?php

namespace App\Membership\Repository;

use App\Membership\Entity\Plan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Plan>
 */
class PlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Plan::class);
    }

    public function save(Plan $plan, bool $flush = true): void
    {
        $this->getEntityManager()->persist($plan);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Plan[]
     */
    public function findActivePlans(): array
    {
        return $this->findBy(['isActive' => true], ['price' => 'ASC']);
    }
}
