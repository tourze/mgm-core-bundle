<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\MgmCoreBundle\Entity\Referral;
use Tourze\MgmCoreBundle\Enum\ReferralState;
use Tourze\MgmCoreBundle\Repository\ReferralRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(ReferralRepository::class)]
#[RunTestsInSeparateProcesses]
class ReferralRepositoryTest extends AbstractRepositoryTestCase
{
    protected function onSetUp(): void
    {
    }

    protected function createNewEntity(): object
    {
        $r = new Referral();
        $uniqueId = uniqid();
        $r->setId('ref-' . $uniqueId);
        $r->setCampaignId('campaign-' . $uniqueId);
        $r->setReferrerType('user');
        $r->setReferrerId('referrer-' . $uniqueId);
        $r->setRefereeType('user');
        $r->setRefereeId('referee-' . $uniqueId);
        $r->setToken(null);
        $r->setSource('web');
        $r->setState(ReferralState::CREATED);
        $r->setCreateTime(new \DateTimeImmutable());

        return $r;
    }

    protected function getRepository(): ReferralRepository
    {
        $repository = self::getContainer()->get(ReferralRepository::class);
        self::assertInstanceOf(ReferralRepository::class, $repository);

        return $repository;
    }

    public function testSave(): void
    {
        $referral = $this->createTestReferral();

        $this->getRepository()->save($referral, true);

        $found = $this->getRepository()->find('referral-1');
        $this->assertNotNull($found);
        $this->assertSame('referral-1', $found->getId());
        $this->assertSame('campaign-123', $found->getCampaignId());
        $this->assertSame('user', $found->getReferrerType());
        $this->assertStringStartsWith('referrer-', $found->getReferrerId());
        $this->assertSame('user', $found->getRefereeType());
        $this->assertSame('referee-referral-1', $found->getRefereeId());
        $this->assertSame('web', $found->getSource());
        $this->assertSame(ReferralState::CREATED, $found->getState());
    }

    public function testSaveWithoutFlush(): void
    {
        $referral = $this->createTestReferral('referral-no-flush');

        $this->getRepository()->save($referral, false);

        // 手动flush以验证数据已持久化
        self::getEntityManager()->flush();

        $found = $this->getRepository()->find('referral-no-flush');
        $this->assertNotNull($found);
        $this->assertSame('campaign-123', $found->getCampaignId());
    }

    public function testRemove(): void
    {
        $referral = $this->createTestReferral('referral-to-remove');
        $this->getRepository()->save($referral, true);

        $this->getRepository()->remove($referral, true);

        $found = $this->getRepository()->find('referral-to-remove');
        $this->assertNull($found);
    }

    // testRemoveWithoutFlush() 由基类提供

    public function testFindByCampaignAndReferee(): void
    {
        // 创建匹配的推荐关系
        $referral1 = $this->createTestReferral('referral-match');
        $referral1->setCampaignId('campaign-match');
        $referral1->setRefereeType('user');
        $referral1->setRefereeId('referee-match');
        $this->getRepository()->save($referral1, true);

        // 创建不匹配活动的推荐关系
        $referral2 = $this->createTestReferral('referral-different-campaign');
        $referral2->setCampaignId('campaign-different');
        $referral2->setRefereeType('user');
        $referral2->setRefereeId('referee-match');
        $this->getRepository()->save($referral2, true);

        // 创建不匹配被推荐人的推荐关系
        $referral3 = $this->createTestReferral('referral-different-referee');
        $referral3->setCampaignId('campaign-match');
        $referral3->setRefereeType('user');
        $referral3->setRefereeId('referee-different');
        $this->getRepository()->save($referral3, true);

        // 测试查找匹配的推荐关系
        $result = $this->getRepository()->findByCampaignAndReferee(
            'campaign-match',
            'user',
            'referee-match'
        );
        $this->assertNotNull($result);
        $this->assertSame('referral-match', $result->getId());

        // 测试查找不存在的推荐关系
        $result = $this->getRepository()->findByCampaignAndReferee(
            'campaign-nonexistent',
            'user',
            'referee-match'
        );
        $this->assertNull($result);
    }

    public function testFindByCampaignAndReferrer(): void
    {
        // 创建多个匹配推荐人的推荐关系
        $referral1 = $this->createTestReferral('referral-1');
        $referral1->setCampaignId('campaign-match');
        $referral1->setReferrerType('user');
        $referral1->setReferrerId('referrer-match');
        $referral1->setState(ReferralState::CREATED);
        $this->getRepository()->save($referral1, true);

        $referral2 = $this->createTestReferral('referral-2');
        $referral2->setCampaignId('campaign-match');
        $referral2->setReferrerType('user');
        $referral2->setReferrerId('referrer-match');
        $referral2->setState(ReferralState::QUALIFIED);
        $this->getRepository()->save($referral2, true);

        $referral3 = $this->createTestReferral('referral-3');
        $referral3->setCampaignId('campaign-match');
        $referral3->setReferrerType('user');
        $referral3->setReferrerId('referrer-match');
        $referral3->setState(ReferralState::REWARDED);
        $this->getRepository()->save($referral3, true);

        // 创建不匹配的推荐关系
        $referral4 = $this->createTestReferral('referral-different');
        $referral4->setCampaignId('campaign-different');
        $referral4->setReferrerType('user');
        $referral4->setReferrerId('referrer-match');
        $this->getRepository()->save($referral4, true);

        // 测试查找所有状态的推荐关系
        $results = $this->getRepository()->findByCampaignAndReferrer(
            'campaign-match',
            'user',
            'referrer-match'
        );
        $this->assertCount(3, $results);

        $referralIds = array_map(fn (Referral $r) => $r->getId(), $results);
        $this->assertContains('referral-1', $referralIds);
        $this->assertContains('referral-2', $referralIds);
        $this->assertContains('referral-3', $referralIds);

        // 测试查找特定状态的推荐关系
        $results = $this->getRepository()->findByCampaignAndReferrer(
            'campaign-match',
            'user',
            'referrer-match',
            ReferralState::QUALIFIED
        );
        $this->assertCount(1, $results);
        $this->assertSame('referral-2', $results[0]->getId());
        $this->assertSame(ReferralState::QUALIFIED, $results[0]->getState());
    }

    public function testFindByCampaignAndReferrerWithNoResults(): void
    {
        $results = $this->getRepository()->findByCampaignAndReferrer(
            'campaign-nonexistent',
            'user',
            'referrer-nonexistent'
        );
        $this->assertCount(0, $results);

        $results = $this->getRepository()->findByCampaignAndReferrer(
            'campaign-nonexistent',
            'user',
            'referrer-nonexistent',
            ReferralState::CREATED
        );
        $this->assertCount(0, $results);
    }

    public function testExistsByCampaignAndParticipants(): void
    {
        // 创建推荐关系
        $referral = $this->createTestReferral('referral-exists');
        $referral->setCampaignId('campaign-exists');
        $referral->setReferrerType('user');
        $referral->setReferrerId('referrer-exists');
        $referral->setRefereeType('user');
        $referral->setRefereeId('referee-exists');
        $this->getRepository()->save($referral, true);

        // 测试存在的推荐关系
        $exists = $this->getRepository()->existsByCampaignAndParticipants(
            'campaign-exists',
            'user',
            'referrer-exists',
            'user',
            'referee-exists'
        );
        $this->assertTrue($exists);

        // 测试不存在的推荐关系
        $exists = $this->getRepository()->existsByCampaignAndParticipants(
            'campaign-nonexistent',
            'user',
            'referrer-exists',
            'user',
            'referee-exists'
        );
        $this->assertFalse($exists);

        $exists = $this->getRepository()->existsByCampaignAndParticipants(
            'campaign-exists',
            'user',
            'referrer-different',
            'user',
            'referee-exists'
        );
        $this->assertFalse($exists);

        $exists = $this->getRepository()->existsByCampaignAndParticipants(
            'campaign-exists',
            'user',
            'referrer-exists',
            'admin',
            'referee-exists'
        );
        $this->assertFalse($exists);
    }

    public function testReferralWithOptionalToken(): void
    {
        // 测试带token的推荐关系
        $referralWithToken = $this->createTestReferral('referral-with-token');
        $referralWithToken->setToken('token-abc123');
        $this->getRepository()->save($referralWithToken, true);

        // 测试不带token的推荐关系
        $referralWithoutToken = $this->createTestReferral('referral-without-token');
        $referralWithoutToken->setToken(null);
        $this->getRepository()->save($referralWithoutToken, true);

        $foundWithToken = $this->getRepository()->find('referral-with-token');
        $this->assertNotNull($foundWithToken);
        $this->assertSame('token-abc123', $foundWithToken->getToken());

        $foundWithoutToken = $this->getRepository()->find('referral-without-token');
        $this->assertNotNull($foundWithoutToken);
        $this->assertNull($foundWithoutToken->getToken());
    }

    public function testReferralWithDifferentStates(): void
    {
        $states = [
            ReferralState::CREATED,
            ReferralState::ATTRIBUTED,
            ReferralState::QUALIFIED,
            ReferralState::REWARDED,
            ReferralState::REVOKED,
        ];

        foreach ($states as $index => $state) {
            $referral = $this->createTestReferral("referral-state-{$index}");
            $referral->setState($state);
            $this->getRepository()->save($referral, true);

            $found = $this->getRepository()->find("referral-state-{$index}");
            $this->assertNotNull($found);
            $this->assertSame($state, $found->getState());
        }
    }

    public function testReferralWithTimeStamps(): void
    {
        $createTime = new \DateTimeImmutable('2023-12-01 10:00:00');
        $qualifyTime = new \DateTimeImmutable('2023-12-01 11:00:00');
        $rewardTime = new \DateTimeImmutable('2023-12-01 12:00:00');

        $referral = $this->createTestReferral('referral-timestamps');
        $referral->setCreateTime($createTime);
        $referral->setQualifyTime($qualifyTime);
        $referral->setRewardTime($rewardTime);
        $this->getRepository()->save($referral, true);

        $found = $this->getRepository()->find('referral-timestamps');
        $this->assertNotNull($found);
        $this->assertEquals($createTime, $found->getCreateTime());
        $this->assertEquals($qualifyTime, $found->getQualifyTime());
        $this->assertEquals($rewardTime, $found->getRewardTime());
    }

    public function testReferralWithNullTimeStamps(): void
    {
        $referral = $this->createTestReferral('referral-null-timestamps');
        $referral->setQualifyTime(null);
        $referral->setRewardTime(null);
        $this->getRepository()->save($referral, true);

        $found = $this->getRepository()->find('referral-null-timestamps');
        $this->assertNotNull($found);
        $this->assertNull($found->getQualifyTime());
        $this->assertNull($found->getRewardTime());
    }

    public function testReferralWithDifferentParticipantTypes(): void
    {
        // 测试用户到用户的推荐
        $userToUser = $this->createTestReferral('user-to-user');
        $userToUser->setReferrerType('user');
        $userToUser->setReferrerId('user-123');
        $userToUser->setRefereeType('user');
        $userToUser->setRefereeId('user-456');
        $this->getRepository()->save($userToUser, true);

        // 测试管理员到用户的推荐
        $adminToUser = $this->createTestReferral('admin-to-user');
        $adminToUser->setReferrerType('admin');
        $adminToUser->setReferrerId('admin-123');
        $adminToUser->setRefereeType('user');
        $adminToUser->setRefereeId('user-789');
        $this->getRepository()->save($adminToUser, true);

        // 测试商户到用户的推荐
        $merchantToUser = $this->createTestReferral('merchant-to-user');
        $merchantToUser->setReferrerType('merchant');
        $merchantToUser->setReferrerId('merchant-123');
        $merchantToUser->setRefereeType('user');
        $merchantToUser->setRefereeId('user-999');
        $this->getRepository()->save($merchantToUser, true);

        $foundUserToUser = $this->getRepository()->find('user-to-user');
        $this->assertNotNull($foundUserToUser);
        $this->assertSame('user', $foundUserToUser->getReferrerType());
        $this->assertSame('user', $foundUserToUser->getRefereeType());

        $foundAdminToUser = $this->getRepository()->find('admin-to-user');
        $this->assertNotNull($foundAdminToUser);
        $this->assertSame('admin', $foundAdminToUser->getReferrerType());
        $this->assertSame('user', $foundAdminToUser->getRefereeType());

        $foundMerchantToUser = $this->getRepository()->find('merchant-to-user');
        $this->assertNotNull($foundMerchantToUser);
        $this->assertSame('merchant', $foundMerchantToUser->getReferrerType());
        $this->assertSame('user', $foundMerchantToUser->getRefereeType());
    }

    public function testReferralWithDifferentSources(): void
    {
        $sources = ['web', 'app', 'email', 'sms', 'social', 'direct'];

        foreach ($sources as $index => $source) {
            $referral = $this->createTestReferral("referral-source-{$index}");
            $referral->setSource($source);
            $this->getRepository()->save($referral, true);

            $found = $this->getRepository()->find("referral-source-{$index}");
            $this->assertNotNull($found);
            $this->assertSame($source, $found->getSource());
        }
    }

    public function testReferralStringRepresentation(): void
    {
        $referral = $this->createTestReferral('referral-string');
        $referral->setReferrerType('user');
        $referral->setReferrerId('referrer-123');
        $referral->setRefereeType('merchant');
        $referral->setRefereeId('referee-456');

        $this->assertSame('user:referrer-123 -> merchant:referee-456', (string) $referral);
    }

    public function testComplexReferralScenario(): void
    {
        // 模拟一个完整的推荐流程
        $referral = $this->createTestReferral('complex-referral');
        $referral->setCampaignId('holiday-campaign');
        $referral->setReferrerType('user');
        $referral->setReferrerId('power-user-123');
        $referral->setRefereeType('user');
        $referral->setRefereeId('new-user-456');
        $referral->setToken('referral-token-xyz789');
        $referral->setSource('social');
        $referral->setState(ReferralState::CREATED);

        $this->getRepository()->save($referral, true);

        // 模拟状态变更：归因
        $referral->setState(ReferralState::ATTRIBUTED);
        $this->getRepository()->save($referral, true);

        // 模拟状态变更：合格
        $qualifyTime = new \DateTimeImmutable();
        $referral->setState(ReferralState::QUALIFIED);
        $referral->setQualifyTime($qualifyTime);
        $this->getRepository()->save($referral, true);

        // 模拟状态变更：奖励
        $rewardTime = new \DateTimeImmutable();
        $referral->setState(ReferralState::REWARDED);
        $referral->setRewardTime($rewardTime);
        $this->getRepository()->save($referral, true);

        $final = $this->getRepository()->find('complex-referral');
        $this->assertNotNull($final);
        $this->assertSame(ReferralState::REWARDED, $final->getState());
        $this->assertEquals($qualifyTime, $final->getQualifyTime());
        $this->assertEquals($rewardTime, $final->getRewardTime());

        // 验证查询功能
        $exists = $this->getRepository()->existsByCampaignAndParticipants(
            'holiday-campaign',
            'user',
            'power-user-123',
            'user',
            'new-user-456'
        );
        $this->assertTrue($exists);

        $refereeResult = $this->getRepository()->findByCampaignAndReferee(
            'holiday-campaign',
            'user',
            'new-user-456'
        );
        $this->assertNotNull($refereeResult);
        $this->assertSame('complex-referral', $refereeResult->getId());

        $referrerResults = $this->getRepository()->findByCampaignAndReferrer(
            'holiday-campaign',
            'user',
            'power-user-123',
            ReferralState::REWARDED
        );
        $this->assertCount(1, $referrerResults);
        $this->assertSame('complex-referral', $referrerResults[0]->getId());
    }

    private function createTestReferral(string $id = 'referral-1'): Referral
    {
        $uniqueId = uniqid();
        $referral = new Referral();
        $referral->setId($id);
        $referral->setCampaignId('campaign-123');
        $referral->setReferrerType('user');
        $referral->setReferrerId('referrer-' . $uniqueId);
        $referral->setRefereeType('user');
        $referral->setRefereeId('referee-' . $id);
        $referral->setToken('token-' . $id);
        $referral->setSource('web');
        $referral->setState(ReferralState::CREATED);
        $referral->setCreateTime(new \DateTimeImmutable());

        return $referral;
    }
}
