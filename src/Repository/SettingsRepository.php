<?php

namespace App\Repository;

use App\Entity\Settings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Settings>
 */
class SettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Settings::class);
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function findValueByKey(string $key): ?string
    {
        return $this->createQueryBuilder('s')
            ->select('s.value')
            ->andWhere('s.settingKey = :key')
            ->setParameter('key', $key)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
