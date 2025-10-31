<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\MgmCoreBundle\Entity\Reward;
use Tourze\MgmCoreBundle\Enum\Beneficiary;
use Tourze\MgmCoreBundle\Enum\RewardState;
use Tourze\MgmCoreBundle\Repository\RewardRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(RewardRepository::class)]
#[RunTestsInSeparateProcesses]
class RewardRepositoryTest extends AbstractRepositoryTestCase
{
    protected function onSetUp(): void
    {
    }

    protected function createNewEntity(): object
    {
        $reward = new Reward();
        $reward->setId('rew-' . uniqid());
        $reward->setReferralId('ref-' . uniqid());
        $reward->setBeneficiary(Beneficiary::REFERRER);
        $reward->setBeneficiaryType('user');
        $reward->setBeneficiaryId('user-' . uniqid());
        $reward->setType('points');
        $reward->setSpecJson([]);
        $reward->setState(RewardState::PENDING);
        $reward->setExternalIssueId(null);
        $reward->setIdemKey('idem-' . uniqid());
        $reward->setCreateTime(new \DateTimeImmutable());

        return $reward;
    }

    protected function getRepository(): RewardRepository
    {
        $repository = self::getContainer()->get(RewardRepository::class);
        self::assertInstanceOf(RewardRepository::class, $repository);

        return $repository;
    }

    public function testSave(): void
    {
        $reward = $this->createTestReward();

        $this->getRepository()->save($reward, true);

        $found = $this->getRepository()->find('reward-1');
        $this->assertNotNull($found);
        $this->assertSame('reward-1', $found->getId());
        $this->assertSame('referral-123', $found->getReferralId());
        $this->assertSame(Beneficiary::REFERRER, $found->getBeneficiary());
        $this->assertSame('points', $found->getType());
        $this->assertSame(['amount' => 100, 'currency' => 'USD'], $found->getSpecJson());
        $this->assertSame(RewardState::PENDING, $found->getState());
        $this->assertSame('idem-key-reward-1', $found->getIdemKey());
    }

    public function testSaveWithoutFlush(): void
    {
        $reward = $this->createTestReward('reward-no-flush');

        $this->getRepository()->save($reward, false);

        // 手动flush以验证数据已持久化
        self::getEntityManager()->flush();

        $found = $this->getRepository()->find('reward-no-flush');
        $this->assertNotNull($found);
        $this->assertSame('referral-123', $found->getReferralId());
    }

    public function testRemove(): void
    {
        $reward = $this->createTestReward('reward-to-remove');
        $this->getRepository()->save($reward, true);

        $this->getRepository()->remove($reward, true);

        $found = $this->getRepository()->find('reward-to-remove');
        $this->assertNull($found);
    }

    // testRemoveWithoutFlush() 由基类提供

    public function testFindByReferralId(): void
    {
        // 创建多个与同一推荐相关的奖励
        $reward1 = $this->createTestReward('reward-1');
        $reward1->setReferralId('referral-match');
        $reward1->setBeneficiary(Beneficiary::REFERRER);
        $this->getRepository()->save($reward1, true);

        $reward2 = $this->createTestReward('reward-2');
        $reward2->setReferralId('referral-match');
        $reward2->setBeneficiary(Beneficiary::REFEREE);
        $reward2->setType('discount');
        $this->getRepository()->save($reward2, true);

        // 创建不匹配的奖励
        $reward3 = $this->createTestReward('reward-3');
        $reward3->setReferralId('referral-different');
        $this->getRepository()->save($reward3, true);

        // 查找匹配的奖励
        $results = $this->getRepository()->findByReferralId('referral-match');
        $this->assertCount(2, $results);

        $rewardIds = array_map(fn (Reward $r) => $r->getId(), $results);
        $this->assertContains('reward-1', $rewardIds);
        $this->assertContains('reward-2', $rewardIds);

        // 查找不匹配的条件
        $results = $this->getRepository()->findByReferralId('referral-nonexistent');
        $this->assertCount(0, $results);
    }

    public function testFindByIdemKey(): void
    {
        // 创建多个不同幂等性键的奖励
        $reward1 = $this->createTestReward('reward-idem-1');
        $reward1->setIdemKey('unique-idem-key-1');
        $this->getRepository()->save($reward1, true);

        $reward2 = $this->createTestReward('reward-idem-2');
        $reward2->setIdemKey('unique-idem-key-2');
        $this->getRepository()->save($reward2, true);

        // 测试查找存在的幂等性键
        $result = $this->getRepository()->findByIdemKey('unique-idem-key-1');
        $this->assertNotNull($result);
        $this->assertSame('reward-idem-1', $result->getId());
        $this->assertSame('unique-idem-key-1', $result->getIdemKey());

        // 测试查找另一个存在的幂等性键
        $result = $this->getRepository()->findByIdemKey('unique-idem-key-2');
        $this->assertNotNull($result);
        $this->assertSame('reward-idem-2', $result->getId());

        // 测试查找不存在的幂等性键
        $result = $this->getRepository()->findByIdemKey('non-existent-key');
        $this->assertNull($result);
    }

    public function testFindByBeneficiaryAndState(): void
    {
        // 清理已存在的数据
        self::getEntityManager()->createQuery('DELETE FROM ' . Reward::class)->execute();

        // 创建不同受益人和状态的奖励
        $referrerPending = $this->createTestReward('referrer-pending');
        $referrerPending->setBeneficiary(Beneficiary::REFERRER);
        $referrerPending->setState(RewardState::PENDING);
        $this->getRepository()->save($referrerPending, true);

        $referrerGranted = $this->createTestReward('referrer-granted');
        $referrerGranted->setBeneficiary(Beneficiary::REFERRER);
        $referrerGranted->setState(RewardState::GRANTED);
        $this->getRepository()->save($referrerGranted, true);

        $refereePending = $this->createTestReward('referee-pending');
        $refereePending->setBeneficiary(Beneficiary::REFEREE);
        $refereePending->setState(RewardState::PENDING);
        $this->getRepository()->save($refereePending, true);

        $refereeCancelled = $this->createTestReward('referee-cancelled');
        $refereeCancelled->setBeneficiary(Beneficiary::REFEREE);
        $refereeCancelled->setState(RewardState::CANCELLED);
        $this->getRepository()->save($refereeCancelled, true);

        // 测试查找推荐人的待发放奖励
        $results = $this->getRepository()->findByBeneficiaryAndState(Beneficiary::REFERRER, RewardState::PENDING);
        $this->assertCount(1, $results);
        $this->assertSame('referrer-pending', $results[0]->getId());

        // 测试查找推荐人的已发放奖励
        $results = $this->getRepository()->findByBeneficiaryAndState(Beneficiary::REFERRER, RewardState::GRANTED);
        $this->assertCount(1, $results);
        $this->assertSame('referrer-granted', $results[0]->getId());

        // 测试查找被推荐人的待发放奖励
        $results = $this->getRepository()->findByBeneficiaryAndState(Beneficiary::REFEREE, RewardState::PENDING);
        $this->assertCount(1, $results);
        $this->assertSame('referee-pending', $results[0]->getId());

        // 测试查找被推荐人的已取消奖励
        $results = $this->getRepository()->findByBeneficiaryAndState(Beneficiary::REFEREE, RewardState::CANCELLED);
        $this->assertCount(1, $results);
        $this->assertSame('referee-cancelled', $results[0]->getId());

        // 测试查找不存在的组合
        $results = $this->getRepository()->findByBeneficiaryAndState(Beneficiary::REFERRER, RewardState::CANCELLED);
        $this->assertCount(0, $results);
    }

    public function testFindByExternalIssueId(): void
    {
        // 创建带外部发放ID的奖励
        $rewardWithExternal = $this->createTestReward('reward-with-external');
        $rewardWithExternal->setExternalIssueId('external-123');
        $rewardWithExternal->setType('coupon');
        $this->getRepository()->save($rewardWithExternal, true);

        $rewardWithoutExternal = $this->createTestReward('reward-without-external');
        $rewardWithoutExternal->setExternalIssueId(null);
        $this->getRepository()->save($rewardWithoutExternal, true);

        $anotherRewardWithExternal = $this->createTestReward('another-reward-with-external');
        $anotherRewardWithExternal->setExternalIssueId('external-456');
        $anotherRewardWithExternal->setType('voucher');
        $this->getRepository()->save($anotherRewardWithExternal, true);

        // 测试查找存在的外部发放ID和类型
        $result = $this->getRepository()->findByExternalIssueId('external-123', 'coupon');
        $this->assertNotNull($result);
        $this->assertSame('reward-with-external', $result->getId());
        $this->assertSame('external-123', $result->getExternalIssueId());
        $this->assertSame('coupon', $result->getType());

        // 测试查找另一个存在的外部发放ID和类型
        $result = $this->getRepository()->findByExternalIssueId('external-456', 'voucher');
        $this->assertNotNull($result);
        $this->assertSame('another-reward-with-external', $result->getId());

        // 测试查找不存在的外部发放ID
        $result = $this->getRepository()->findByExternalIssueId('non-existent-external', 'coupon');
        $this->assertNull($result);

        // 测试查找存在的外部发放ID但类型不匹配
        $result = $this->getRepository()->findByExternalIssueId('external-123', 'voucher');
        $this->assertNull($result);
    }

    public function testRewardWithComplexSpecJson(): void
    {
        $complexSpec = [
            'amount' => 250.50,
            'currency' => 'EUR',
            'validity' => [
                'start_date' => '2023-12-01',
                'end_date' => '2024-12-01',
            ],
            'restrictions' => [
                'min_order_amount' => 50.0,
                'categories' => ['electronics', 'books'],
                'max_uses' => 3,
            ],
            'metadata' => [
                'campaign_id' => 'holiday-2023',
                'tier_level' => 'gold',
            ],
        ];

        $reward = $this->createTestReward('reward-complex-spec');
        $reward->setSpecJson($complexSpec);
        $this->getRepository()->save($reward, true);

        $found = $this->getRepository()->find('reward-complex-spec');
        $this->assertNotNull($found);

        $spec = $found->getSpecJson();
        $this->assertIsArray($spec);
        $this->assertSame(250.50, $spec['amount']);
        $this->assertArrayHasKey('validity', $spec);
        $this->assertArrayHasKey('restrictions', $spec);
        $this->assertSame('2023-12-01', $spec['validity']['start_date']);
        $this->assertCount(2, $spec['restrictions']['categories']);
        $this->assertSame('holiday-2023', $spec['metadata']['campaign_id']);
    }

    public function testRewardWithEmptySpecJson(): void
    {
        $reward = $this->createTestReward('reward-empty-spec');
        $reward->setSpecJson([]);
        $this->getRepository()->save($reward, true);

        $found = $this->getRepository()->find('reward-empty-spec');
        $this->assertNotNull($found);
        $this->assertSame([], $found->getSpecJson());
    }

    public function testRewardWithDifferentBeneficiaries(): void
    {
        // 测试推荐人奖励
        $referrerReward = $this->createTestReward('referrer-reward');
        $referrerReward->setBeneficiary(Beneficiary::REFERRER);
        $referrerReward->setType('cashback');
        $this->getRepository()->save($referrerReward, true);

        // 测试被推荐人奖励
        $refereeReward = $this->createTestReward('referee-reward');
        $refereeReward->setBeneficiary(Beneficiary::REFEREE);
        $refereeReward->setType('welcome_bonus');
        $this->getRepository()->save($refereeReward, true);

        $foundReferrer = $this->getRepository()->find('referrer-reward');
        $this->assertNotNull($foundReferrer);
        $this->assertSame(Beneficiary::REFERRER, $foundReferrer->getBeneficiary());
        $this->assertSame('cashback', $foundReferrer->getType());

        $foundReferee = $this->getRepository()->find('referee-reward');
        $this->assertNotNull($foundReferee);
        $this->assertSame(Beneficiary::REFEREE, $foundReferee->getBeneficiary());
        $this->assertSame('welcome_bonus', $foundReferee->getType());
    }

    public function testRewardWithDifferentStates(): void
    {
        $states = [RewardState::PENDING, RewardState::GRANTED, RewardState::CANCELLED];

        foreach ($states as $index => $state) {
            $reward = $this->createTestReward("reward-state-{$index}");
            $reward->setState($state);
            $this->getRepository()->save($reward, true);

            $found = $this->getRepository()->find("reward-state-{$index}");
            $this->assertNotNull($found);
            $this->assertSame($state, $found->getState());
        }
    }

    public function testRewardWithDifferentTypes(): void
    {
        $types = ['points', 'cashback', 'coupon', 'discount', 'voucher', 'gift'];

        foreach ($types as $index => $type) {
            $reward = $this->createTestReward("reward-type-{$index}");
            $reward->setType($type);
            $this->getRepository()->save($reward, true);

            $found = $this->getRepository()->find("reward-type-{$index}");
            $this->assertNotNull($found);
            $this->assertSame($type, $found->getType());
        }
    }

    public function testRewardWithTimeStamps(): void
    {
        $createTime = new \DateTimeImmutable('2023-12-01 10:00:00');
        $grantTime = new \DateTimeImmutable('2023-12-01 11:00:00');
        $revokeTime = new \DateTimeImmutable('2023-12-01 12:00:00');

        $reward = $this->createTestReward('reward-timestamps');
        $reward->setCreateTime($createTime);
        $reward->setGrantTime($grantTime);
        $reward->setRevokeTime($revokeTime);
        $this->getRepository()->save($reward, true);

        $found = $this->getRepository()->find('reward-timestamps');
        $this->assertNotNull($found);
        $this->assertEquals($createTime, $found->getCreateTime());
        $this->assertEquals($grantTime, $found->getGrantTime());
        $this->assertEquals($revokeTime, $found->getRevokeTime());
    }

    public function testRewardWithNullTimeStamps(): void
    {
        $reward = $this->createTestReward('reward-null-timestamps');
        $reward->setGrantTime(null);
        $reward->setRevokeTime(null);
        $this->getRepository()->save($reward, true);

        $found = $this->getRepository()->find('reward-null-timestamps');
        $this->assertNotNull($found);
        $this->assertNull($found->getGrantTime());
        $this->assertNull($found->getRevokeTime());
    }

    public function testRewardWithNullExternalIssueId(): void
    {
        $reward = $this->createTestReward('reward-null-external');
        $reward->setExternalIssueId(null);
        $this->getRepository()->save($reward, true);

        $found = $this->getRepository()->find('reward-null-external');
        $this->assertNotNull($found);
        $this->assertNull($found->getExternalIssueId());
    }

    public function testRewardStringRepresentation(): void
    {
        // 测试推荐人的待发放积分奖励
        $referrerReward = $this->createTestReward('referrer-points');
        $referrerReward->setBeneficiary(Beneficiary::REFERRER);
        $referrerReward->setType('points');
        $referrerReward->setState(RewardState::PENDING);

        $this->assertSame('referrer - points (pending)', (string) $referrerReward);

        // 测试被推荐人的已发放折扣奖励
        $refereeReward = $this->createTestReward('referee-discount');
        $refereeReward->setBeneficiary(Beneficiary::REFEREE);
        $refereeReward->setType('discount');
        $refereeReward->setState(RewardState::GRANTED);

        $this->assertSame('referee - discount (granted)', (string) $refereeReward);
    }

    public function testComplexRewardScenario(): void
    {
        // 模拟一个完整的奖励发放流程
        $reward = $this->createTestReward('complex-reward');
        $reward->setReferralId('holiday-referral-123');
        $reward->setBeneficiary(Beneficiary::REFERRER);
        $reward->setType('cashback');
        $reward->setSpecJson([
            'amount' => 50.0,
            'currency' => 'USD',
            'minimum_order' => 100.0,
        ]);
        $reward->setState(RewardState::PENDING);
        $reward->setIdemKey('holiday-referral-123-referrer-cashback');
        $reward->setExternalIssueId(null);

        $this->getRepository()->save($reward, true);

        // 模拟奖励发放
        $grantTime = new \DateTimeImmutable();
        $reward->setState(RewardState::GRANTED);
        $reward->setGrantTime($grantTime);
        $reward->setExternalIssueId('external-payment-456');
        $this->getRepository()->save($reward, true);

        $final = $this->getRepository()->find('complex-reward');
        $this->assertNotNull($final);
        $this->assertSame(RewardState::GRANTED, $final->getState());
        $this->assertEquals($grantTime, $final->getGrantTime());
        $this->assertSame('external-payment-456', $final->getExternalIssueId());

        // 验证查询功能
        $referralRewards = $this->getRepository()->findByReferralId('holiday-referral-123');
        $this->assertCount(1, $referralRewards);
        $this->assertSame('complex-reward', $referralRewards[0]->getId());

        $idemResult = $this->getRepository()->findByIdemKey('holiday-referral-123-referrer-cashback');
        $this->assertNotNull($idemResult);
        $this->assertSame('complex-reward', $idemResult->getId());

        $beneficiaryStateResults = $this->getRepository()->findByBeneficiaryAndState(Beneficiary::REFERRER, RewardState::GRANTED);
        $grantedIds = array_map(fn (Reward $r) => $r->getId(), $beneficiaryStateResults);
        $this->assertContains('complex-reward', $grantedIds);

        $externalResult = $this->getRepository()->findByExternalIssueId('external-payment-456', 'cashback');
        $this->assertNotNull($externalResult);
        $this->assertSame('complex-reward', $externalResult->getId());
    }

    public function testMultipleRewardsForSameReferral(): void
    {
        $referralId = 'referral-multiple-rewards';

        // 推荐人奖励
        $referrerReward = $this->createTestReward('referrer-reward-multi');
        $referrerReward->setReferralId($referralId);
        $referrerReward->setBeneficiary(Beneficiary::REFERRER);
        $referrerReward->setType('points');
        $referrerReward->setIdemKey('multi-referrer-points');
        $this->getRepository()->save($referrerReward, true);

        // 被推荐人奖励1
        $refereeReward1 = $this->createTestReward('referee-reward-1-multi');
        $refereeReward1->setReferralId($referralId);
        $refereeReward1->setBeneficiary(Beneficiary::REFEREE);
        $refereeReward1->setType('discount');
        $refereeReward1->setIdemKey('multi-referee-discount');
        $this->getRepository()->save($refereeReward1, true);

        // 被推荐人奖励2
        $refereeReward2 = $this->createTestReward('referee-reward-2-multi');
        $refereeReward2->setReferralId($referralId);
        $refereeReward2->setBeneficiary(Beneficiary::REFEREE);
        $refereeReward2->setType('coupon');
        $refereeReward2->setIdemKey('multi-referee-coupon');
        $this->getRepository()->save($refereeReward2, true);

        $results = $this->getRepository()->findByReferralId($referralId);
        $this->assertCount(3, $results);

        $rewardTypes = array_map(fn (Reward $r) => $r->getType(), $results);
        $this->assertContains('points', $rewardTypes);
        $this->assertContains('discount', $rewardTypes);
        $this->assertContains('coupon', $rewardTypes);

        // 验证每个奖励都有唯一的幂等性键
        $idemKeys = array_map(fn (Reward $r) => $r->getIdemKey(), $results);
        $this->assertCount(3, array_unique($idemKeys));
    }

    private function createTestReward(string $id = 'reward-1'): Reward
    {
        $reward = new Reward();
        $reward->setId($id);
        $reward->setReferralId('referral-123');
        $reward->setBeneficiary(Beneficiary::REFERRER);
        $reward->setBeneficiaryType('user');
        $reward->setBeneficiaryId('user-123');
        $reward->setType('points');
        $reward->setSpecJson(['amount' => 100, 'currency' => 'USD']);
        $reward->setState(RewardState::PENDING);
        $reward->setExternalIssueId('external-123');
        $reward->setIdemKey('idem-key-' . $id);
        $reward->setCreateTime(new \DateTimeImmutable());

        return $reward;
    }
}
