<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\MgmCoreBundle\Entity\AttributionToken;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<AttributionToken>
 */
#[AsRepository(entityClass: AttributionToken::class)]
class AttributionTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AttributionToken::class);
    }

    public function findValidToken(string $token): ?AttributionToken
    {
        return $this->createQueryBuilder('t')
            ->where('t.token = :token')
            ->andWhere('t.expireTime > :now')
            ->setParameter('token', $token)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @return AttributionToken[]
     */
    public function findByCampaignAndReferrer(string $campaignId, string $referrerType, string $referrerId): array
    {
        return $this->findBy([
            'campaignId' => $campaignId,
            'referrerType' => $referrerType,
            'referrerId' => $referrerId,
        ]);
    }

    public function deleteExpiredTokens(\DateTimeInterface $before): int
    {
        return $this->createQueryBuilder('t')
            ->delete()
            ->where('t.expireTime < :before')
            ->setParameter('before', $before)
            ->getQuery()
            ->execute()
        ;
    }

    public function save(AttributionToken $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AttributionToken $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
