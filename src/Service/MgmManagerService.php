<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\MgmCoreBundle\DTO\Evidence;
use Tourze\MgmCoreBundle\DTO\QualificationResult;
use Tourze\MgmCoreBundle\DTO\Subject;
use Tourze\MgmCoreBundle\Entity\Campaign;
use Tourze\MgmCoreBundle\Entity\Referral;
use Tourze\MgmCoreBundle\Enum\Attribution;
use Tourze\MgmCoreBundle\Enum\ReferralState;
use Tourze\MgmCoreBundle\Exception\CampaignException;
use Tourze\MgmCoreBundle\Repository\CampaignRepository;
use Tourze\MgmCoreBundle\Repository\ReferralRepository;

#[Autoconfigure(public: true)]
class MgmManagerService
{
    public function __construct(
        private CampaignRepository $campaignRepository,
        private ReferralRepository $referralRepository,
        private EntityManagerInterface $entityManager,
        private AttributionService $attributionService,
        private ReferralService $referralService,
        private IdempotencyService $idempotencyService,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    /**
     * @param array<string, mixed> $config
     */
    public function createCampaign(array $config): string
    {
        $campaign = new Campaign();
        $campaign->setId($this->idGenerator->generate());
        $campaign->setName($config['name']);
        $campaign->setActive($config['active'] ?? true);
        $campaign->setConfigJson($config);
        $campaign->setWindowDays($config['windowDays'] ?? 7);
        $campaign->setAttribution(Attribution::from($config['attribution'] ?? 'last'));
        $campaign->setSelfBlock($config['selfBlock'] ?? true);
        $campaign->setBudgetLimit(array_key_exists('budgetLimit', $config) ? (string) $config['budgetLimit'] : null);

        $this->entityManager->persist($campaign);
        $this->entityManager->flush();

        return $campaign->getId();
    }

    /**
     * @param array<string, mixed> $opts
     */
    public function generateReferralToken(string $campaignId, Subject $referrer, array $opts = []): string
    {
        $campaign = $this->campaignRepository->findActiveById($campaignId);
        if (null === $campaign) {
            throw CampaignException::campaignNotFound($campaignId);
        }

        return $this->attributionService->generateToken($campaign, $referrer);
    }

    public function bindReferral(string $campaignId, Subject $referrer, Subject $referee, string $source, string $idemKey): string
    {
        $result = $this->idempotencyService->getOrStore(
            $idemKey,
            'bind_referral',
            function () use ($campaignId, $referrer, $referee, $source) {
                $campaign = $this->campaignRepository->findActiveById($campaignId);
                if (null === $campaign) {
                    throw CampaignException::campaignNotFound($campaignId);
                }

                $referral = $this->referralService->bindReferral($campaign, $referrer, $referee, $source);

                return $referral->getId();
            }
        );

        return is_array($result) && array_key_exists('result', $result) ? (string) $result['result'] : (string) $result;
    }

    public function ingestEvidence(string $campaignId, Subject $referee, Evidence $evidence, string $idemKey): QualificationResult
    {
        $result = $this->idempotencyService->getOrStore(
            $idemKey,
            'ingest_evidence',
            function () use ($campaignId, $referee) {
                $referral = $this->referralRepository->findByCampaignAndReferee(
                    $campaignId,
                    $referee->type,
                    $referee->id
                );

                if (null === $referral) {
                    return new QualificationResult('noop', 'No referral found');
                }

                if (ReferralState::ATTRIBUTED !== $referral->getState()) {
                    return new QualificationResult('noop', 'Referral already processed');
                }

                $this->referralService->updateState($referral, ReferralState::QUALIFIED);

                return new QualificationResult('qualified', null, $referral->getId());
            }
        );

        return is_array($result) && array_key_exists('result', $result) && $result['result'] instanceof QualificationResult
            ? $result['result']
            : ($result instanceof QualificationResult ? $result : new QualificationResult('noop', 'Invalid idempotency result'));
    }

    public function getReferral(string $referralId): ?Referral
    {
        return $this->referralRepository->find($referralId);
    }
}
