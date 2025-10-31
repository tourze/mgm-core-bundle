<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\MgmCoreBundle\Entity\Referral;
use Tourze\MgmCoreBundle\Enum\ReferralState;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<Referral>
 */
#[AsRepository(entityClass: Referral::class)]
class ReferralRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Referral::class);
    }

    public function findByCampaignAndReferee(string $campaignId, string $refereeType, string $refereeId): ?Referral
    {
        return $this->findOneBy([
            'campaignId' => $campaignId,
            'refereeType' => $refereeType,
            'refereeId' => $refereeId,
        ]);
    }

    /**
     * @return Referral[]
     */
    public function findByCampaignAndReferrer(string $campaignId, string $referrerType, string $referrerId, ?ReferralState $state = null): array
    {
        $criteria = [
            'campaignId' => $campaignId,
            'referrerType' => $referrerType,
            'referrerId' => $referrerId,
        ];

        if (null !== $state) {
            $criteria['state'] = $state;
        }

        return $this->findBy($criteria);
    }

    public function existsByCampaignAndParticipants(string $campaignId, string $referrerType, string $referrerId, string $refereeType, string $refereeId): bool
    {
        return $this->count([
            'campaignId' => $campaignId,
            'referrerType' => $referrerType,
            'referrerId' => $referrerId,
            'refereeType' => $refereeType,
            'refereeId' => $refereeId,
        ]) > 0;
    }

    public function save(Referral $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Referral $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
