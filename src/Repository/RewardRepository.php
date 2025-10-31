<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\MgmCoreBundle\Entity\Reward;
use Tourze\MgmCoreBundle\Enum\Beneficiary;
use Tourze\MgmCoreBundle\Enum\RewardState;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<Reward>
 */
#[AsRepository(entityClass: Reward::class)]
class RewardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reward::class);
    }

    /**
     * @return Reward[]
     */
    public function findByReferralId(string $referralId): array
    {
        return $this->findBy(['referralId' => $referralId]);
    }

    public function findByIdemKey(string $idemKey): ?Reward
    {
        return $this->findOneBy(['idemKey' => $idemKey]);
    }

    /**
     * @return Reward[]
     */
    public function findByBeneficiaryAndState(Beneficiary $beneficiary, RewardState $state): array
    {
        return $this->findBy([
            'beneficiary' => $beneficiary,
            'state' => $state,
        ]);
    }

    public function findByExternalIssueId(string $externalIssueId, string $type): ?Reward
    {
        return $this->findOneBy([
            'externalIssueId' => $externalIssueId,
            'type' => $type,
        ]);
    }

    public function save(Reward $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Reward $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
