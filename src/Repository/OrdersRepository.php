<?php

namespace App\Repository;

use App\Entity\Orders;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Orders>
 */
class OrdersRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Orders::class);
    }

    /**
     * @param string $uniqueId
     * @return Orders|null
     */
    public function findByUniqueId(string $uniqueId): ?Orders
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.custom_id = :uniqueId')
            ->setParameter('uniqueId', $uniqueId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
