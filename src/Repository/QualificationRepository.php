<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\MgmCoreBundle\Entity\Qualification;
use Tourze\MgmCoreBundle\Enum\Decision;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<Qualification>
 */
#[AsRepository(entityClass: Qualification::class)]
class QualificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Qualification::class);
    }

    /**
     * @return Qualification[]
     */
    public function findByReferralId(string $referralId): array
    {
        return $this->findBy(['referralId' => $referralId]);
    }

    /**
     * @return Qualification|null
     */
    public function findLatestByReferralId(string $referralId): ?Qualification
    {
        $result = $this->createQueryBuilder('q')
            ->where('q.referralId = :referralId')
            ->orderBy('q.occurTime', 'DESC')
            ->setParameter('referralId', $referralId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $result instanceof Qualification ? $result : null;
    }

    public function countByDecision(Decision $decision): int
    {
        return $this->count(['decision' => $decision]);
    }

    public function save(Qualification $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Qualification $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
