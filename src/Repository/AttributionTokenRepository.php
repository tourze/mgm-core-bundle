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

    /**
     * @return AttributionToken|null
     */
    public function findValidToken(string $token): ?AttributionToken
    {
        $result = $this->createQueryBuilder('t')
            ->where('t.token = :token')
            ->andWhere('t.expireTime > :now')
            ->setParameter('token', $token)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $result instanceof AttributionToken ? $result : null;
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

    /**
     * @return int
     */
    public function deleteExpiredTokens(\DateTimeInterface $before): int
    {
        $result = $this->createQueryBuilder('t')
            ->delete()
            ->where('t.expireTime < :before')
            ->setParameter('before', $before)
            ->getQuery()
            ->execute()
        ;

        return is_numeric($result) ? (int) $result : 0;
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
