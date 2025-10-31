<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Tourze\MgmCoreBundle\DTO\Subject;
use Tourze\MgmCoreBundle\Entity\Campaign;
use Tourze\MgmCoreBundle\Entity\Referral;
use Tourze\MgmCoreBundle\Enum\ReferralState;
use Tourze\MgmCoreBundle\Exception\ReferralException;
use Tourze\MgmCoreBundle\Repository\ReferralRepository;

class ReferralService
{
    public function __construct(
        private ReferralRepository $repository,
        private EntityManagerInterface $entityManager,
        private ClockInterface $clock,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    public function bindReferral(
        Campaign $campaign,
        Subject $referrer,
        Subject $referee,
        string $source,
        ?string $token = null,
    ): Referral {
        if ($campaign->isSelfBlock() && $referrer->type === $referee->type && $referrer->id === $referee->id) {
            throw ReferralException::selfReferralNotAllowed();
        }

        if ($this->repository->existsByCampaignAndParticipants(
            $campaign->getId(),
            $referrer->type,
            $referrer->id,
            $referee->type,
            $referee->id
        )) {
            throw ReferralException::duplicateReferral();
        }

        $referral = new Referral();
        $referral->setId($this->idGenerator->generate());
        $referral->setCampaignId($campaign->getId());
        $referral->setReferrerType($referrer->type);
        $referral->setReferrerId($referrer->id);
        $referral->setRefereeType($referee->type);
        $referral->setRefereeId($referee->id);
        $referral->setToken($token);
        $referral->setSource($source);
        $referral->setState(ReferralState::ATTRIBUTED);
        $referral->setCreateTime($this->clock->now());

        $this->entityManager->persist($referral);
        $this->entityManager->flush();

        return $referral;
    }

    public function updateState(Referral $referral, ReferralState $newState): void
    {
        $referral->setState($newState);

        $now = $this->clock->now();

        if (ReferralState::QUALIFIED === $newState) {
            $referral->setQualifyTime($now);
        } elseif (ReferralState::REWARDED === $newState) {
            $referral->setRewardTime($now);
        }

        $this->entityManager->flush();
    }
}
