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
        $this->validateCampaignConfig($config);

        $active = $this->convertToBool($config['active'] ?? true);
        $windowDays = $this->convertToInt($config['windowDays'] ?? 7, 'Window days');
        $attribution = $this->validateAttribution($config['attribution'] ?? 'last');
        $selfBlock = $this->convertToBool($config['selfBlock'] ?? true);
        $budgetLimit = $this->convertBudgetLimit($config['budgetLimit'] ?? null);

        $campaign = new Campaign();
        $campaign->setId($this->idGenerator->generate());
        $campaign->setName($this->validateString($config['name'], 'Campaign name'));
        $campaign->setActive($active);
        $campaign->setConfigJson($config);
        $campaign->setWindowDays($windowDays);
        $campaign->setAttribution(Attribution::from($attribution));
        $campaign->setSelfBlock($selfBlock);
        $campaign->setBudgetLimit($budgetLimit);

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

        return $this->convertToString($result);
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

    /**
     * 验证活动配置
     *
     * @param array<string, mixed> $config
     */
    private function validateCampaignConfig(array $config): void
    {
        if (!isset($config['name'])) {
            throw new \InvalidArgumentException('Campaign name is required');
        }
    }

    /**
     * 验证字符串类型
     */
    private function validateString(mixed $value, string $fieldName): string
    {
        if (is_string($value)) {
            return $value;
        }

        throw new \InvalidArgumentException("{$fieldName} must be a string");
    }

    /**
     * 将混合类型转换为布尔值
     */
    private function convertToBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return (bool) $value;
    }

    /**
     * 将混合类型转换为整数
     */
    private function convertToInt(mixed $value, string $fieldName): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        throw new \InvalidArgumentException("{$fieldName} must be an integer");
    }

    /**
     * 验证归因参数
     */
    private function validateAttribution(mixed $value): string|int
    {
        if (is_string($value) || is_int($value)) {
            return $value;
        }

        throw new \InvalidArgumentException('Attribution must be a string or integer');
    }

    /**
     * 转换预算限制
     */
    private function convertBudgetLimit(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        if (is_string($value)) {
            return $value;
        }

        throw new \InvalidArgumentException('Budget limit must be numeric or string');
    }

    /**
     * 将混合类型转换为字符串
     */
    private function convertToString(mixed $value): string
    {
        // 处理数组类型（幂等性结果）
        if (is_array($value) && array_key_exists('result', $value)) {
            return $this->convertArrayResultToString($value);
        }

        // 处理基本类型
        return $this->convertBasicTypeToString($value);
    }

    /**
     * 将数组结果转换为字符串
     *
     * @param array{result: mixed} $value
     */
    private function convertArrayResultToString(array $value): string
    {
        if ($value['result'] === null) {
            throw new \RuntimeException('Idempotency result cannot be null');
        }
        return $this->convertToString($value['result']);
    }

    /**
     * 将基本类型转换为字符串
     */
    private function convertBasicTypeToString(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_null($value)) {
            throw new \RuntimeException('Idempotency result cannot be null');
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        throw new \RuntimeException('Cannot convert value to string: ' . gettype($value));
    }
}
