<?php

namespace App\Repository;

use App\Entity\Coupon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Coupon>
 */
class CouponRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Coupon::class);
    }

    /**
     * @param string $code
     * @return Coupon|null
     */
    public function findByCode(string $code): ?Coupon
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.code = :code')
            ->andWhere('c.status = 1')
            ->andWhere('(c.date_from IS NULL OR c.date_from <= CURRENT_DATE())')
            ->andWhere('(c.date_to IS NULL OR c.date_to >= CURRENT_DATE())')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
