<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
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
use Tourze\MgmCoreBundle\Service\AttributionService;
use Tourze\MgmCoreBundle\Service\ClockInterface;
use Tourze\MgmCoreBundle\Service\MgmManagerService;
use Tourze\MgmCoreBundle\Service\ReferralService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(MgmManagerService::class)]
#[RunTestsInSeparateProcesses]
class MgmManagerServiceTest extends AbstractIntegrationTestCase
{
    private MgmManagerService $mgmManagerService;

    private CampaignRepository $campaignRepository;

    private ReferralRepository $referralRepository;

    private AttributionService $attributionService;

    private ReferralService $referralService;

    private ClockInterface $clock;

    protected function onSetUp(): void
    {
        $this->mgmManagerService = self::getService(MgmManagerService::class);
        $this->campaignRepository = self::getService(CampaignRepository::class);
        $this->referralRepository = self::getService(ReferralRepository::class);
        $this->attributionService = self::getService(AttributionService::class);
        $this->referralService = self::getService(ReferralService::class);
        $this->clock = self::getService(ClockInterface::class);
    }

    public function testCreateCampaign(): void
    {
        $config = [
            'name' => 'Test Campaign',
            'active' => true,
            'windowDays' => 7,
            'attribution' => 'last',
            'selfBlock' => true,
        ];

        $campaignId = $this->mgmManagerService->createCampaign($config);

        $this->assertNotEmpty($campaignId);
        $this->assertIsString($campaignId);

        // 验证活动确实被创建
        $campaign = $this->campaignRepository->find($campaignId);
        $this->assertInstanceOf(Campaign::class, $campaign);
        $this->assertSame($config['name'], $campaign->getName());
        $this->assertTrue($campaign->isActive());
        $this->assertSame($config['windowDays'], $campaign->getWindowDays());
        $this->assertSame(Attribution::LAST, $campaign->getAttribution());
        $this->assertTrue($campaign->isSelfBlock());
        $this->assertSame($config, $campaign->getConfigJson());
        $this->assertNotNull($campaign->getCreateTime());
        $this->assertNotNull($campaign->getUpdateTime());
    }

    public function testCreateCampaignWithDefaultValues(): void
    {
        $config = [
            'name' => 'Minimal Campaign',
        ];

        $campaignId = $this->mgmManagerService->createCampaign($config);

        $campaign = $this->campaignRepository->find($campaignId);
        $this->assertInstanceOf(Campaign::class, $campaign);
        $this->assertSame('Minimal Campaign', $campaign->getName());
        $this->assertTrue($campaign->isActive()); // 默认为true
        $this->assertSame(7, $campaign->getWindowDays()); // 默认为7
        $this->assertSame(Attribution::LAST, $campaign->getAttribution()); // 默认为last
        $this->assertTrue($campaign->isSelfBlock()); // 默认为true
        $this->assertNull($campaign->getBudgetLimit()); // 默认为null
    }

    public function testCreateCampaignWithCustomValues(): void
    {
        $config = [
            'name' => 'Custom Campaign',
            'active' => false,
            'windowDays' => 30,
            'attribution' => 'first',
            'selfBlock' => false,
            'budgetLimit' => 10000,
            'customField' => 'customValue',
        ];

        $campaignId = $this->mgmManagerService->createCampaign($config);

        $campaign = $this->campaignRepository->find($campaignId);
        $this->assertInstanceOf(Campaign::class, $campaign);
        $this->assertFalse($campaign->isActive());
        $this->assertSame(30, $campaign->getWindowDays());
        $this->assertSame(Attribution::FIRST, $campaign->getAttribution());
        $this->assertFalse($campaign->isSelfBlock());
        $this->assertSame(10000, $campaign->getBudgetLimit());
        $this->assertSame($config, $campaign->getConfigJson());
    }

    public function testGenerateReferralToken(): void
    {
        $campaignId = $this->createTestCampaign();
        $referrer = new Subject('user', '123');

        $token = $this->mgmManagerService->generateReferralToken($campaignId, $referrer);

        $this->assertNotEmpty($token);
        $this->assertIsString($token);

        // 验证token是通过AttributionService生成的
        $attributionToken = $this->attributionService->validateToken($token);
        $this->assertNotNull($attributionToken);
        $this->assertSame($campaignId, $attributionToken->getCampaignId());
        $this->assertSame($referrer->type, $attributionToken->getReferrerType());
        $this->assertSame($referrer->id, $attributionToken->getReferrerId());
    }

    public function testGenerateReferralTokenWithOptions(): void
    {
        $campaignId = $this->createTestCampaign();
        $referrer = new Subject('merchant', 'shop_456');
        $opts = ['some' => 'option'];

        $token = $this->mgmManagerService->generateReferralToken($campaignId, $referrer, $opts);

        $this->assertNotEmpty($token);

        // 验证token仍然正确生成（options目前不影响生成过程）
        $attributionToken = $this->attributionService->validateToken($token);
        $this->assertNotNull($attributionToken);
        $this->assertSame('merchant', $attributionToken->getReferrerType());
        $this->assertSame('shop_456', $attributionToken->getReferrerId());
    }

    public function testGenerateReferralTokenWithInvalidCampaign(): void
    {
        $this->expectException(CampaignException::class);
        $this->expectExceptionMessage('Campaign not found: non-existent-campaign');

        $referrer = new Subject('user', '123');
        $this->mgmManagerService->generateReferralToken('non-existent-campaign', $referrer);
    }

    public function testGenerateReferralTokenWithInactiveCampaign(): void
    {
        $campaignId = $this->createTestCampaign(['active' => false]);

        $this->expectException(CampaignException::class);

        $referrer = new Subject('user', '123');
        $this->mgmManagerService->generateReferralToken($campaignId, $referrer);
    }

    public function testBindReferral(): void
    {
        $campaignId = $this->createTestCampaign();
        $referrer = new Subject('user', '123');
        $referee = new Subject('user', '456');
        $source = 'web';
        $idemKey = 'bind-key-' . uniqid();

        $referralId = $this->mgmManagerService->bindReferral($campaignId, $referrer, $referee, $source, $idemKey);

        $this->assertNotEmpty($referralId);
        $this->assertIsString($referralId);

        // 验证推荐关系确实被创建
        $referral = $this->referralRepository->find($referralId);
        $this->assertInstanceOf(Referral::class, $referral);
        $this->assertSame($campaignId, $referral->getCampaignId());
        $this->assertSame($referrer->type, $referral->getReferrerType());
        $this->assertSame($referrer->id, $referral->getReferrerId());
        $this->assertSame($referee->type, $referral->getRefereeType());
        $this->assertSame($referee->id, $referral->getRefereeId());
        $this->assertSame($source, $referral->getSource());
        $this->assertSame(ReferralState::ATTRIBUTED, $referral->getState());
    }

    public function testBindReferralIdempotency(): void
    {
        $campaignId = $this->createTestCampaign();
        $referrer = new Subject('user', '123');
        $referee = new Subject('user', '456');
        $source = 'app';
        $idemKey = 'idempotent-bind-' . uniqid();

        // 第一次调用
        $referralId1 = $this->mgmManagerService->bindReferral($campaignId, $referrer, $referee, $source, $idemKey);

        // 第二次调用相同的幂等键，应该返回相同的结果
        $referralId2 = $this->mgmManagerService->bindReferral($campaignId, $referrer, $referee, $source, $idemKey);

        $this->assertSame($referralId1, $referralId2);

        // 验证数据库中只有一条推荐记录
        $referrals = $this->referralRepository->findBy(['campaignId' => $campaignId]);
        $this->assertCount(1, $referrals);
    }

    public function testBindReferralWithInvalidCampaign(): void
    {
        $this->expectException(CampaignException::class);
        $this->expectExceptionMessage('Campaign not found: invalid-campaign');

        $referrer = new Subject('user', '123');
        $referee = new Subject('user', '456');
        $this->mgmManagerService->bindReferral('invalid-campaign', $referrer, $referee, 'web', 'idem-key');
    }

    public function testIngestEvidence(): void
    {
        // 先创建推荐关系
        $campaignId = $this->createTestCampaign();
        $referrer = new Subject('user', '123');
        $referee = new Subject('user', '456');
        $referralId = $this->mgmManagerService->bindReferral(
            $campaignId,
            $referrer,
            $referee,
            'web',
            'bind-idem-' . uniqid()
        );

        $evidence = new Evidence('purchase', 'order_789', $this->clock->now(), ['amount' => 100]);
        $idemKey = 'evidence-key-' . uniqid();

        $result = $this->mgmManagerService->ingestEvidence($campaignId, $referee, $evidence, $idemKey);

        $this->assertInstanceOf(QualificationResult::class, $result);
        $this->assertSame('qualified', $result->status);
        $this->assertNull($result->reason);
        $this->assertSame($referralId, $result->referralId);

        // 验证推荐状态已更新
        $referral = $this->referralRepository->find($referralId);
        $this->assertNotNull($referral);
        $this->assertSame(ReferralState::QUALIFIED, $referral->getState());
        $this->assertNotNull($referral->getQualifyTime());
    }

    public function testIngestEvidenceWithNoReferral(): void
    {
        $campaignId = $this->createTestCampaign();
        $referee = new Subject('user', '999'); // 没有推荐关系的用户
        $evidence = new Evidence('purchase', 'order_999', $this->clock->now());
        $idemKey = 'evidence-noop-' . uniqid();

        $result = $this->mgmManagerService->ingestEvidence($campaignId, $referee, $evidence, $idemKey);

        $this->assertInstanceOf(QualificationResult::class, $result);
        $this->assertSame('noop', $result->status);
        $this->assertSame('No referral found', $result->reason);
        $this->assertNull($result->referralId);
    }

    public function testIngestEvidenceWithAlreadyProcessedReferral(): void
    {
        // 创建并处理推荐关系
        $campaignId = $this->createTestCampaign();
        $referrer = new Subject('user', '123');
        $referee = new Subject('user', '456');
        $referralId = $this->mgmManagerService->bindReferral(
            $campaignId,
            $referrer,
            $referee,
            'web',
            'bind-idem-' . uniqid()
        );

        // 手动更新状态为已处理
        $referral = $this->referralRepository->find($referralId);
        $this->assertNotNull($referral);
        $this->referralService->updateState($referral, ReferralState::REWARDED);

        $evidence = new Evidence('purchase', 'order_123', $this->clock->now());
        $idemKey = 'evidence-processed-' . uniqid();

        $result = $this->mgmManagerService->ingestEvidence($campaignId, $referee, $evidence, $idemKey);

        $this->assertInstanceOf(QualificationResult::class, $result);
        $this->assertSame('noop', $result->status);
        $this->assertSame('Referral already processed', $result->reason);
        $this->assertNull($result->referralId);
    }

    public function testIngestEvidenceIdempotency(): void
    {
        $campaignId = $this->createTestCampaign();
        $referrer = new Subject('user', '123');
        $referee = new Subject('user', '456');
        $this->mgmManagerService->bindReferral($campaignId, $referrer, $referee, 'web', 'bind-' . uniqid());

        $evidence = new Evidence('purchase', 'order_123', $this->clock->now());
        $idemKey = 'evidence-idempotent-' . uniqid();

        // 两次相同的证据摄入
        $result1 = $this->mgmManagerService->ingestEvidence($campaignId, $referee, $evidence, $idemKey);
        $result2 = $this->mgmManagerService->ingestEvidence($campaignId, $referee, $evidence, $idemKey);

        $this->assertSame($result1->status, $result2->status);
        $this->assertSame($result1->reason, $result2->reason);
        $this->assertSame($result1->referralId, $result2->referralId);
    }

    public function testGetReferral(): void
    {
        $campaignId = $this->createTestCampaign();
        $referrer = new Subject('user', '123');
        $referee = new Subject('user', '456');
        $referralId = $this->mgmManagerService->bindReferral(
            $campaignId,
            $referrer,
            $referee,
            'web',
            'bind-' . uniqid()
        );

        $referral = $this->mgmManagerService->getReferral($referralId);

        $this->assertInstanceOf(Referral::class, $referral);
        $this->assertSame($referralId, $referral->getId());
        $this->assertSame($campaignId, $referral->getCampaignId());
        $this->assertSame($referrer->type, $referral->getReferrerType());
        $this->assertSame($referrer->id, $referral->getReferrerId());
        $this->assertSame($referee->type, $referral->getRefereeType());
        $this->assertSame($referee->id, $referral->getRefereeId());
    }

    public function testGetReferralWithNonExistentId(): void
    {
        $result = $this->mgmManagerService->getReferral('non-existent-id');

        $this->assertNull($result);
    }

    public function testCompleteWorkflow(): void
    {
        // 1. 创建活动
        $campaignId = $this->mgmManagerService->createCampaign([
            'name' => 'Complete Workflow Test',
            'active' => true,
            'windowDays' => 14,
        ]);

        // 2. 生成推荐token
        $referrer = new Subject('user', 'referrer_123');
        $token = $this->mgmManagerService->generateReferralToken($campaignId, $referrer);
        $this->assertNotEmpty($token);

        // 3. 绑定推荐关系
        $referee = new Subject('user', 'referee_456');
        $referralId = $this->mgmManagerService->bindReferral(
            $campaignId,
            $referrer,
            $referee,
            'social_media',
            'bind-complete-' . uniqid()
        );

        // 4. 摄入证据，资格验证
        $evidence = new Evidence('signup', 'account_789', $this->clock->now());
        $result = $this->mgmManagerService->ingestEvidence(
            $campaignId,
            $referee,
            $evidence,
            'evidence-complete-' . uniqid()
        );

        $this->assertSame('qualified', $result->status);
        $this->assertSame($referralId, $result->referralId);

        // 5. 验证最终状态
        $finalReferral = $this->mgmManagerService->getReferral($referralId);
        $this->assertNotNull($finalReferral);
        $this->assertSame(ReferralState::QUALIFIED, $finalReferral->getState());
        $this->assertNotNull($finalReferral->getQualifyTime());
    }

    public function testWorkflowWithMultipleReferees(): void
    {
        $campaignId = $this->createTestCampaign();
        $referrer = new Subject('user', 'referrer_123');

        // 绑定多个被推荐人
        $referee1 = new Subject('user', 'referee_001');
        $referee2 = new Subject('user', 'referee_002');
        $referee3 = new Subject('user', 'referee_003');

        $referralId1 = $this->mgmManagerService->bindReferral(
            $campaignId, $referrer, $referee1, 'email', 'bind-1-' . uniqid()
        );
        $referralId2 = $this->mgmManagerService->bindReferral(
            $campaignId, $referrer, $referee2, 'sms', 'bind-2-' . uniqid()
        );
        $referralId3 = $this->mgmManagerService->bindReferral(
            $campaignId, $referrer, $referee3, 'web', 'bind-3-' . uniqid()
        );

        // 只有一个被推荐人提供证据
        $evidence = new Evidence('purchase', 'order_001', $this->clock->now());
        $result1 = $this->mgmManagerService->ingestEvidence(
            $campaignId, $referee1, $evidence, 'evidence-1-' . uniqid()
        );

        $this->assertSame('qualified', $result1->status);
        $this->assertSame($referralId1, $result1->referralId);

        // 验证其他推荐关系未受影响
        $referral2 = $this->mgmManagerService->getReferral($referralId2);
        $referral3 = $this->mgmManagerService->getReferral($referralId3);
        $this->assertNotNull($referral2);
        $this->assertNotNull($referral3);

        $this->assertSame(ReferralState::ATTRIBUTED, $referral2->getState());
        $this->assertSame(ReferralState::ATTRIBUTED, $referral3->getState());
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createTestCampaign(array $config = []): string
    {
        $defaultConfig = [
            'name' => 'Test Campaign',
            'active' => true,
            'windowDays' => 7,
            'attribution' => 'last',
            'selfBlock' => true,
        ];

        return $this->mgmManagerService->createCampaign(array_merge($defaultConfig, $config));
    }
}
