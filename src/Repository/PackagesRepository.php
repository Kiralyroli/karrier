<?php

namespace App\Repository;

use App\Entity\Packages;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Packages>
 */
class PackagesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Packages::class);
    }

    /**
     * @param string $level
     * @return Packages[]
     */
    public function findByLevel(string $level): array
    {
        return $this->createQueryBuilder('p')
                ->andWhere('p.level = :level')
                ->setParameter('level', $level)
                ->getQuery()
                ->getResult();
    }
}
