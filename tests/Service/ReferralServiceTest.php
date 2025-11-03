<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\MgmCoreBundle\DTO\Subject;
use Tourze\MgmCoreBundle\Entity\Campaign;
use Tourze\MgmCoreBundle\Entity\Referral;
use Tourze\MgmCoreBundle\Enum\Attribution;
use Tourze\MgmCoreBundle\Enum\ReferralState;
use Tourze\MgmCoreBundle\Exception\ReferralException;
use Tourze\MgmCoreBundle\Repository\ReferralRepository;
use Tourze\MgmCoreBundle\Service\ClockInterface;
use Tourze\MgmCoreBundle\Service\IdGeneratorInterface;
use Tourze\MgmCoreBundle\Service\ReferralService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(ReferralService::class)]
#[RunTestsInSeparateProcesses]
class ReferralServiceTest extends AbstractIntegrationTestCase
{
    private ReferralService $referralService;

    private ReferralRepository $repository;

    private ClockInterface $clock;

    private IdGeneratorInterface $idGenerator;

    protected function onSetUp(): void
    {
        $this->referralService = self::getService(ReferralService::class);
        $this->repository = self::getService(ReferralRepository::class);
        $this->clock = self::getService(ClockInterface::class);
        $this->idGenerator = self::getService(IdGeneratorInterface::class);
    }

    public function testBindReferral(): void
    {
        $campaign = $this->createTestCampaign();
        $referrer = new Subject('user', '123');
        $referee = new Subject('user', '456');
        $source = 'web';

        $referral = $this->referralService->bindReferral($campaign, $referrer, $referee, $source);

        $this->assertInstanceOf(Referral::class, $referral);
        $this->assertNotEmpty($referral->getId());
        $this->assertSame($campaign->getId(), $referral->getCampaignId());
        $this->assertSame($referrer->type, $referral->getReferrerType());
        $this->assertSame($referrer->id, $referral->getReferrerId());
        $this->assertSame($referee->type, $referral->getRefereeType());
        $this->assertSame($referee->id, $referral->getRefereeId());
        $this->assertSame($source, $referral->getSource());
        $this->assertSame(ReferralState::ATTRIBUTED, $referral->getState());
        $this->assertNotNull($referral->getCreateTime());
        $this->assertNull($referral->getToken()); // 没有提供token

        // 验证推荐关系被正确保存
        $savedReferral = $this->repository->find($referral->getId());
        $this->assertInstanceOf(Referral::class, $savedReferral);
        $this->assertSame($referral->getId(), $savedReferral->getId());
    }

    public function testBindReferralWithToken(): void
    {
        $campaign = $this->createTestCampaign();
        $referrer = new Subject('user', '123');
        $referee = new Subject('user', '456');
        $source = 'email';
        $token = 'attribution-token-123';

        $referral = $this->referralService->bindReferral($campaign, $referrer, $referee, $source, $token);

        $this->assertSame($token, $referral->getToken());

        // 验证token被正确保存
        $savedReferral = $this->repository->find($referral->getId());
        $this->assertNotNull($savedReferral);
        $this->assertSame($token, $savedReferral->getToken());
    }

    public function testBindReferralWithDifferentSubjectTypes(): void
    {
        $campaign = $this->createTestCampaign();
        $referrer = new Subject('merchant', 'shop_789');
        $referee = new Subject('user', '456');
        $source = 'partnership';

        $referral = $this->referralService->bindReferral($campaign, $referrer, $referee, $source);

        $this->assertSame('merchant', $referral->getReferrerType());
        $this->assertSame('shop_789', $referral->getReferrerId());
        $this->assertSame('user', $referral->getRefereeType());
        $this->assertSame('456', $referral->getRefereeId());
    }

    public function testBindReferralSelfReferralBlocked(): void
    {
        $campaign = $this->createTestCampaign(['selfBlock' => true]);
        $referrer = new Subject('user', '123');
        $referee = new Subject('user', '123'); // 同一个用户

        $this->expectException(ReferralException::class);
        $this->expectExceptionMessage('Self-referral not allowed');

        $this->referralService->bindReferral($campaign, $referrer, $referee, 'web');
    }

    public function testBindReferralSelfReferralAllowed(): void
    {
        $campaign = $this->createTestCampaign(['selfBlock' => false]);
        $referrer = new Subject('user', '123');
        $referee = new Subject('user', '123'); // 同一个用户

        $referral = $this->referralService->bindReferral($campaign, $referrer, $referee, 'test');

        $this->assertInstanceOf(Referral::class, $referral);
        $this->assertSame($referrer->id, $referral->getReferrerId());
        $this->assertSame($referee->id, $referral->getRefereeId());
    }

    public function testBindReferralSelfReferralDifferentTypes(): void
    {
        // 即使相同ID，不同type也应该允许
        $campaign = $this->createTestCampaign(['selfBlock' => true]);
        $referrer = new Subject('user', '123');
        $referee = new Subject('merchant', '123'); // 相同ID但不同type

        $referral = $this->referralService->bindReferral($campaign, $referrer, $referee, 'cross_type');

        $this->assertInstanceOf(Referral::class, $referral);
        $this->assertSame('user', $referral->getReferrerType());
        $this->assertSame('merchant', $referral->getRefereeType());
        $this->assertSame('123', $referral->getReferrerId());
        $this->assertSame('123', $referral->getRefereeId());
    }

    public function testBindReferralDuplicateReferral(): void
    {
        $campaign = $this->createTestCampaign();
        $referrer = new Subject('user', '123');
        $referee = new Subject('user', '456');

        // 第一次绑定成功
        $this->referralService->bindReferral($campaign, $referrer, $referee, 'web');

        // 第二次绑定相同的推荐关系应该抛出异常
        $this->expectException(ReferralException::class);
        $this->expectExceptionMessage('Referral already exists');

        $this->referralService->bindReferral($campaign, $referrer, $referee, 'mobile');
    }

    public function testBindReferralDifferentCampaigns(): void
    {
        $campaign1 = $this->createTestCampaign(['name' => 'Campaign 1']);
        $campaign2 = $this->createTestCampaign(['name' => 'Campaign 2']);
        $referrer = new Subject('user', '123');
        $referee = new Subject('user', '456');

        // 在不同活动中绑定相同的推荐关系应该成功
        $referral1 = $this->referralService->bindReferral($campaign1, $referrer, $referee, 'web');
        $referral2 = $this->referralService->bindReferral($campaign2, $referrer, $referee, 'mobile');

        $this->assertInstanceOf(Referral::class, $referral1);
        $this->assertInstanceOf(Referral::class, $referral2);
        $this->assertNotSame($referral1->getId(), $referral2->getId());
        $this->assertSame($campaign1->getId(), $referral1->getCampaignId());
        $this->assertSame($campaign2->getId(), $referral2->getCampaignId());
    }

    public function testUpdateStateToQualified(): void
    {
        $campaign = $this->createTestCampaign();
        $referral = $this->referralService->bindReferral(
            $campaign,
            new Subject('user', '123'),
            new Subject('user', '456'),
            'web'
        );

        $beforeUpdate = $this->clock->now();
        $this->referralService->updateState($referral, ReferralState::QUALIFIED);
        $afterUpdate = $this->clock->now();

        $this->assertSame(ReferralState::QUALIFIED, $referral->getState());
        $this->assertNotNull($referral->getQualifyTime());

        // 验证时间戳准确性
        $qualifyTime = $referral->getQualifyTime();
        $this->assertGreaterThanOrEqual($beforeUpdate->getTimestamp(), $qualifyTime->getTimestamp());
        $this->assertLessThanOrEqual($afterUpdate->getTimestamp(), $qualifyTime->getTimestamp());

        // 验证数据库中的状态已更新
        self::getEntityManager()->clear();
        $updatedReferral = $this->repository->find($referral->getId());
        $this->assertNotNull($updatedReferral);
        $this->assertSame(ReferralState::QUALIFIED, $updatedReferral->getState());
        $this->assertNotNull($updatedReferral->getQualifyTime());
    }

    public function testUpdateStateToRewarded(): void
    {
        $campaign = $this->createTestCampaign();
        $referral = $this->referralService->bindReferral(
            $campaign,
            new Subject('user', '123'),
            new Subject('user', '456'),
            'web'
        );

        $beforeUpdate = $this->clock->now();
        $this->referralService->updateState($referral, ReferralState::REWARDED);
        $afterUpdate = $this->clock->now();

        $this->assertSame(ReferralState::REWARDED, $referral->getState());
        $this->assertNotNull($referral->getRewardTime());

        // 验证时间戳准确性
        $rewardTime = $referral->getRewardTime();
        $this->assertGreaterThanOrEqual($beforeUpdate->getTimestamp(), $rewardTime->getTimestamp());
        $this->assertLessThanOrEqual($afterUpdate->getTimestamp(), $rewardTime->getTimestamp());

        // 验证数据库中的状态已更新
        self::getEntityManager()->clear();
        $updatedReferral = $this->repository->find($referral->getId());
        $this->assertNotNull($updatedReferral);
        $this->assertSame(ReferralState::REWARDED, $updatedReferral->getState());
        $this->assertNotNull($updatedReferral->getRewardTime());
    }

    public function testUpdateStateToOtherStates(): void
    {
        $campaign = $this->createTestCampaign();
        $referral = $this->referralService->bindReferral(
            $campaign,
            new Subject('user', '123'),
            new Subject('user', '456'),
            'web'
        );

        // 测试更新到其他状态（不设置特殊时间戳）
        $this->referralService->updateState($referral, ReferralState::REVOKED);

        $this->assertSame(ReferralState::REVOKED, $referral->getState());
        $this->assertNull($referral->getQualifyTime());
        $this->assertNull($referral->getRewardTime());

        // 验证数据库中的状态已更新
        self::getEntityManager()->clear();
        $updatedReferral = $this->repository->find($referral->getId());
        $this->assertNotNull($updatedReferral);
        $this->assertSame(ReferralState::REVOKED, $updatedReferral->getState());
    }

    public function testUpdateStateMultipleTimes(): void
    {
        $campaign = $this->createTestCampaign();
        $referral = $this->referralService->bindReferral(
            $campaign,
            new Subject('user', '123'),
            new Subject('user', '456'),
            'web'
        );

        // 先更新到QUALIFIED
        $this->referralService->updateState($referral, ReferralState::QUALIFIED);
        $qualifyTime = $referral->getQualifyTime();

        // 再更新到REWARDED
        $this->referralService->updateState($referral, ReferralState::REWARDED);

        $this->assertSame(ReferralState::REWARDED, $referral->getState());
        $this->assertSame($qualifyTime, $referral->getQualifyTime()); // QualifyTime应该保持不变
        $this->assertNotNull($referral->getRewardTime());
        $this->assertNotSame($qualifyTime, $referral->getRewardTime());
    }

    public function testBindReferralTimestampAccuracy(): void
    {
        $campaign = $this->createTestCampaign();
        $beforeBind = $this->clock->now();

        $referral = $this->referralService->bindReferral(
            $campaign,
            new Subject('user', '123'),
            new Subject('user', '456'),
            'web'
        );

        $afterBind = $this->clock->now();
        $createTime = $referral->getCreateTime();

        // 验证创建时间在合理范围内
        $this->assertGreaterThanOrEqual($beforeBind->getTimestamp(), $createTime->getTimestamp());
        $this->assertLessThanOrEqual($afterBind->getTimestamp(), $createTime->getTimestamp());
    }

    public function testBindReferralPersistence(): void
    {
        $campaign = $this->createTestCampaign();
        $referrer = new Subject('user', '123');
        $referee = new Subject('user', '456');

        $referral = $this->referralService->bindReferral($campaign, $referrer, $referee, 'web', 'token-123');

        // 清除实体管理器缓存
        self::getEntityManager()->clear();

        // 重新从数据库获取
        $persistedReferral = $this->repository->find($referral->getId());

        $this->assertInstanceOf(Referral::class, $persistedReferral);
        $this->assertSame($referral->getId(), $persistedReferral->getId());
        $this->assertSame($campaign->getId(), $persistedReferral->getCampaignId());
        $this->assertSame($referrer->type, $persistedReferral->getReferrerType());
        $this->assertSame($referrer->id, $persistedReferral->getReferrerId());
        $this->assertSame($referee->type, $persistedReferral->getRefereeType());
        $this->assertSame($referee->id, $persistedReferral->getRefereeId());
        $this->assertSame('web', $persistedReferral->getSource());
        $this->assertSame('token-123', $persistedReferral->getToken());
        $this->assertSame(ReferralState::ATTRIBUTED, $persistedReferral->getState());
    }

    public function testComplexReferralWorkflow(): void
    {
        $campaign = $this->createTestCampaign();
        $referrer = new Subject('user', 'referrer_123');
        $referee = new Subject('user', 'referee_456');

        // 1. 绑定推荐关系
        $referral = $this->referralService->bindReferral($campaign, $referrer, $referee, 'social', 'token-456');

        $this->assertSame(ReferralState::ATTRIBUTED, $referral->getState());
        $this->assertNull($referral->getQualifyTime());
        $this->assertNull($referral->getRewardTime());

        // 2. 更新为合格状态
        $this->referralService->updateState($referral, ReferralState::QUALIFIED);

        $this->assertSame(ReferralState::QUALIFIED, $referral->getState());
        $this->assertNotNull($referral->getQualifyTime());
        $this->assertNull($referral->getRewardTime());

        // 3. 更新为已奖励状态
        $this->referralService->updateState($referral, ReferralState::REWARDED);

        $this->assertSame(ReferralState::REWARDED, $referral->getState());
        $this->assertNotNull($referral->getQualifyTime());
        $this->assertNotNull($referral->getRewardTime());

        // 验证时间戳逻辑正确（奖励时间应该大于等于合格时间）
        $this->assertGreaterThanOrEqual(
            $referral->getQualifyTime()->getTimestamp(),
            $referral->getRewardTime()->getTimestamp()
        );
    }

    public function testMultipleReferralsInSameCampaign(): void
    {
        $campaign = $this->createTestCampaign();
        $referrer = new Subject('user', 'referrer_123');

        // 创建多个推荐关系
        $referee1 = new Subject('user', 'referee_001');
        $referee2 = new Subject('user', 'referee_002');
        $referee3 = new Subject('merchant', 'shop_003');

        $referral1 = $this->referralService->bindReferral($campaign, $referrer, $referee1, 'email');
        $referral2 = $this->referralService->bindReferral($campaign, $referrer, $referee2, 'sms');
        $referral3 = $this->referralService->bindReferral($campaign, $referrer, $referee3, 'partnership');

        // 验证所有推荐关系都被正确创建
        $this->assertNotSame($referral1->getId(), $referral2->getId());
        $this->assertNotSame($referral1->getId(), $referral3->getId());
        $this->assertNotSame($referral2->getId(), $referral3->getId());

        // 验证所有推荐关系都属于同一活动和推荐人
        $this->assertSame($campaign->getId(), $referral1->getCampaignId());
        $this->assertSame($campaign->getId(), $referral2->getCampaignId());
        $this->assertSame($campaign->getId(), $referral3->getCampaignId());

        $this->assertSame($referrer->id, $referral1->getReferrerId());
        $this->assertSame($referrer->id, $referral2->getReferrerId());
        $this->assertSame($referrer->id, $referral3->getReferrerId());

        // 更新其中一个推荐状态，验证不影响其他推荐
        $this->referralService->updateState($referral2, ReferralState::QUALIFIED);

        $this->assertSame(ReferralState::ATTRIBUTED, $referral1->getState());
        $this->assertSame(ReferralState::QUALIFIED, $referral2->getState());
        $this->assertSame(ReferralState::ATTRIBUTED, $referral3->getState());
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createTestCampaign(array $config = []): Campaign
    {
        $defaultConfig = [
            'name' => 'Test Campaign',
            'active' => true,
            'windowDays' => 7,
            'attribution' => 'last',
            'selfBlock' => true,
        ];

        $config = array_merge($defaultConfig, $config);

        $campaign = new Campaign();
        $campaign->setId($this->idGenerator->generate());

        $this->assertIsString($config['name'], 'Campaign name must be a string');
        $this->assertIsBool($config['active'], 'Campaign active must be a boolean');
        $this->assertIsInt($config['windowDays'], 'Campaign windowDays must be an integer');
        $this->assertIsString($config['attribution'], 'Campaign attribution must be a string');
        $this->assertIsBool($config['selfBlock'], 'Campaign selfBlock must be a boolean');

        $campaign->setName($config['name']);
        $campaign->setActive($config['active']);
        $campaign->setConfigJson($config);
        $campaign->setWindowDays($config['windowDays']);
        $campaign->setAttribution(Attribution::from($config['attribution']));
        $campaign->setSelfBlock($config['selfBlock']);

        self::getEntityManager()->persist($campaign);
        self::getEntityManager()->flush();

        return $campaign;
    }
}
