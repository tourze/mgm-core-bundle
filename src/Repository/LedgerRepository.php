<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\MgmCoreBundle\Entity\Ledger;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<Ledger>
 */
#[AsRepository(entityClass: Ledger::class)]
class LedgerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ledger::class);
    }

    /**
     * @return Ledger[]
     */
    public function findByRewardId(string $rewardId): array
    {
        return $this->findBy(['rewardId' => $rewardId]);
    }

    public function save(Ledger $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Ledger $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
