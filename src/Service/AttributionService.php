<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Service;

use DateInterval;
use Doctrine\ORM\EntityManagerInterface;
use Tourze\MgmCoreBundle\DTO\Subject;
use Tourze\MgmCoreBundle\Entity\AttributionToken;
use Tourze\MgmCoreBundle\Entity\Campaign;
use Tourze\MgmCoreBundle\Repository\AttributionTokenRepository;

class AttributionService
{
    public function __construct(
        private AttributionTokenRepository $repository,
        private EntityManagerInterface $entityManager,
        private ClockInterface $clock,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    public function generateToken(Campaign $campaign, Subject $referrer): string
    {
        $token = $this->idGenerator->generate();
        $now = $this->clock->now();
        $expireTime = \DateTimeImmutable::createFromInterface($now)->add(new \DateInterval('P' . $campaign->getWindowDays() . 'D'));

        $attributionToken = new AttributionToken();
        $attributionToken->setToken($token);
        $attributionToken->setCampaignId($campaign->getId());
        $attributionToken->setReferrerType($referrer->type);
        $attributionToken->setReferrerId($referrer->id);
        $attributionToken->setExpireTime($expireTime);
        $attributionToken->setCreateTime($now);

        $this->entityManager->persist($attributionToken);
        $this->entityManager->flush();

        return $token;
    }

    public function validateToken(string $token): ?AttributionToken
    {
        return $this->repository->findValidToken($token);
    }
}
