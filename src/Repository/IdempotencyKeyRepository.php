<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\MgmCoreBundle\Entity\IdempotencyKey;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<IdempotencyKey>
 */
#[AsRepository(entityClass: IdempotencyKey::class)]
class IdempotencyKeyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IdempotencyKey::class);
    }

    public function findByKey(string $key): ?IdempotencyKey
    {
        return $this->findOneBy(['key' => $key]);
    }

    public function findByKeyAndScope(string $key, string $scope): ?IdempotencyKey
    {
        return $this->findOneBy(['key' => $key, 'scope' => $scope]);
    }

    public function save(IdempotencyKey $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(IdempotencyKey $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
