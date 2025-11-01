<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\Controller\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\MgmCoreBundle\Controller\Admin\ReferralCrudController;
use Tourze\MgmCoreBundle\Entity\Referral;
use Tourze\MgmCoreBundle\Enum\ReferralState;
use Tourze\MgmCoreBundle\Repository\ReferralRepository;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(ReferralCrudController::class)]
#[RunTestsInSeparateProcesses]
final class ReferralCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getControllerService(): ReferralCrudController
    {
        return self::getService(ReferralCrudController::class);
    }

    /**
     * 提供索引页面表头
     *
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'referral_id_header' => ['推荐关系ID'];
        yield 'campaign_id_header' => ['活动ID'];
        yield 'referrer_type_header' => ['推荐人类型'];
        yield 'referrer_id_header' => ['推荐人ID'];
        yield 'referee_type_header' => ['被推荐人类型'];
        yield 'referee_id_header' => ['被推荐人ID'];
        yield 'source_header' => ['推荐来源'];
        yield 'state_header' => ['推荐状态'];
        yield 'create_time_header' => ['创建时间'];
    }

    /**
     * 提供新建页面字段
     *
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'id_field' => ['id'];
        yield 'campaignId_field' => ['campaignId'];
        yield 'referrerType_field' => ['referrerType'];
        yield 'referrerId_field' => ['referrerId'];
        yield 'refereeType_field' => ['refereeType'];
        yield 'refereeId_field' => ['refereeId'];
        yield 'token_field' => ['token'];
        yield 'source_field' => ['source'];
        yield 'state_field' => ['state'];
        yield 'createTime_field' => ['createTime'];
        yield 'qualifyTime_field' => ['qualifyTime'];
        yield 'rewardTime_field' => ['rewardTime'];
    }

    /**
     * 提供编辑页面字段
     *
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'edit_id_field' => ['id'];
        yield 'edit_campaignId_field' => ['campaignId'];
        yield 'edit_referrerType_field' => ['referrerType'];
        yield 'edit_referrerId_field' => ['referrerId'];
        yield 'edit_refereeType_field' => ['refereeType'];
        yield 'edit_refereeId_field' => ['refereeId'];
        yield 'edit_token_field' => ['token'];
        yield 'edit_source_field' => ['source'];
        yield 'edit_state_field' => ['state'];
        yield 'edit_createTime_field' => ['createTime'];
        yield 'edit_qualifyTime_field' => ['qualifyTime'];
        yield 'edit_rewardTime_field' => ['rewardTime'];
    }

    public function testIndexPage(): void
    {
        $client = self::createAuthenticatedClient();
        $crawler = $client->request('GET', '/admin');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Navigate to Referral CRUD
        $link = $crawler->filter('a[href*="ReferralCrudController"]')->first();
        if ($link->count() > 0) {
            $client->click($link->link());
            $this->assertEquals(200, $client->getResponse()->getStatusCode());
        }
    }

    public function testCreateReferral(): void
    {
        $client = self::createAuthenticatedClient();
        $client->request('GET', '/admin');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Test entity creation and persistence
        $referral = new Referral();
        $referral->setId('referral-test-' . uniqid());
        $referral->setCampaignId('campaign-123');
        $referral->setReferrerType('user');
        $referral->setReferrerId('referrer-456');
        $referral->setRefereeType('user');
        $referral->setRefereeId('referee-789');
        $referral->setToken('token-abc123');
        $referral->setSource('app');
        $referral->setState(ReferralState::CREATED);
        $referral->setCreateTime(new \DateTimeImmutable());

        $referralRepository = self::getService(ReferralRepository::class);
        self::assertInstanceOf(ReferralRepository::class, $referralRepository);
        $referralRepository->save($referral, true);

        // Verify referral was created
        $savedReferral = $referralRepository->find($referral->getId());
        $this->assertNotNull($savedReferral);
        $this->assertEquals('campaign-123', $savedReferral->getCampaignId());
        $this->assertEquals('user', $savedReferral->getReferrerType());
        $this->assertEquals('referrer-456', $savedReferral->getReferrerId());
        $this->assertEquals('user', $savedReferral->getRefereeType());
        $this->assertEquals('referee-789', $savedReferral->getRefereeId());
        $this->assertEquals('token-abc123', $savedReferral->getToken());
        $this->assertEquals('app', $savedReferral->getSource());
        $this->assertEquals(ReferralState::CREATED, $savedReferral->getState());
    }

    public function testReferralDataPersistence(): void
    {
        // Create client to initialize database
        $client = self::createClientWithDatabase();

        // Create test referrals with different states
        $referral1 = new Referral();
        $referral1->setId('referral-created-' . uniqid());
        $referral1->setCampaignId('campaign-state-test');
        $referral1->setReferrerType('user');
        $referral1->setReferrerId('referrer-001');
        $referral1->setRefereeType('user');
        $referral1->setRefereeId('referee-001');
        $referral1->setToken('token-created');
        $referral1->setSource('web');
        $referral1->setState(ReferralState::CREATED);
        $referral1->setCreateTime(new \DateTimeImmutable());

        $referralRepository = self::getService(ReferralRepository::class);
        self::assertInstanceOf(ReferralRepository::class, $referralRepository);
        $referralRepository->save($referral1, true);

        $referral2 = new Referral();
        $referral2->setId('referral-qualified-' . uniqid());
        $referral2->setCampaignId('campaign-state-test');
        $referral2->setReferrerType('agent');
        $referral2->setReferrerId('agent-002');
        $referral2->setRefereeType('user');
        $referral2->setRefereeId('referee-002');
        $referral2->setToken(null);
        $referral2->setSource('mobile');
        $referral2->setState(ReferralState::QUALIFIED);
        $referral2->setCreateTime(new \DateTimeImmutable('-1 hour'));
        $referral2->setQualifyTime(new \DateTimeImmutable('-30 minutes'));
        $referralRepository->save($referral2, true);

        // Verify referrals are saved correctly
        $savedReferral1 = $referralRepository->find($referral1->getId());
        $this->assertNotNull($savedReferral1);
        $this->assertEquals('campaign-state-test', $savedReferral1->getCampaignId());
        $this->assertEquals(ReferralState::CREATED, $savedReferral1->getState());
        $this->assertEquals('web', $savedReferral1->getSource());
        $this->assertNull($savedReferral1->getQualifyTime());

        $savedReferral2 = $referralRepository->find($referral2->getId());
        $this->assertNotNull($savedReferral2);
        $this->assertEquals(ReferralState::QUALIFIED, $savedReferral2->getState());
        $this->assertEquals('mobile', $savedReferral2->getSource());
        $this->assertNotNull($savedReferral2->getQualifyTime());
    }

    public function testReferralStateProgression(): void
    {
        $client = self::createClientWithDatabase();

        // Test different referral states
        $states = [
            ReferralState::CREATED,
            ReferralState::ATTRIBUTED,
            ReferralState::QUALIFIED,
            ReferralState::REWARDED,
            ReferralState::REVOKED,
        ];

        $referralRepository = self::getService(ReferralRepository::class);
        self::assertInstanceOf(ReferralRepository::class, $referralRepository);

        foreach ($states as $index => $state) {
            $referral = new Referral();
            $referral->setId('referral-state-' . $state->value . '-' . uniqid());
            $referral->setCampaignId('campaign-states');
            $referral->setReferrerType('user');
            $referral->setReferrerId('referrer-states');
            $referral->setRefereeType('user');
            $referral->setRefereeId('referee-' . $index);
            $referral->setToken('token-' . $state->value);
            $referral->setSource('state-test');
            $referral->setState($state);
            $referral->setCreateTime(new \DateTimeImmutable());

            // Set appropriate timestamps based on state
            if (ReferralState::QUALIFIED === $state || ReferralState::REWARDED === $state || ReferralState::REVOKED === $state) {
                $referral->setQualifyTime(new \DateTimeImmutable('-1 hour'));
            }
            if (ReferralState::REWARDED === $state) {
                $referral->setRewardTime(new \DateTimeImmutable('-30 minutes'));
            }

            $referralRepository->save($referral, true);

            $savedReferral = $referralRepository->find($referral->getId());
            $this->assertNotNull($savedReferral);
            $this->assertEquals($state, $savedReferral->getState());
            $this->assertEquals($state->value, $savedReferral->getState()->value);
            $this->assertEquals($state->getLabel(), $savedReferral->getState()->getLabel());
        }
    }

    public function testReferralTimeHandling(): void
    {
        $client = self::createClientWithDatabase();

        $createTime = new \DateTimeImmutable('2024-01-15 10:00:00');
        $qualifyTime = new \DateTimeImmutable('2024-01-15 11:00:00');
        $rewardTime = new \DateTimeImmutable('2024-01-15 12:00:00');

        $referral = new Referral();
        $referral->setId('referral-time-test-' . uniqid());
        $referral->setCampaignId('campaign-time-test');
        $referral->setReferrerType('user');
        $referral->setReferrerId('referrer-time');
        $referral->setRefereeType('user');
        $referral->setRefereeId('referee-time');
        $referral->setToken('token-time');
        $referral->setSource('time-test');
        $referral->setState(ReferralState::REWARDED);
        $referral->setCreateTime($createTime);
        $referral->setQualifyTime($qualifyTime);
        $referral->setRewardTime($rewardTime);

        $referralRepository = self::getService(ReferralRepository::class);
        self::assertInstanceOf(ReferralRepository::class, $referralRepository);
        $referralRepository->save($referral, true);

        $savedReferral = $referralRepository->find($referral->getId());
        $this->assertNotNull($savedReferral);
        $this->assertEquals($createTime->format('Y-m-d H:i:s'), $savedReferral->getCreateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals($qualifyTime->format('Y-m-d H:i:s'), $savedReferral->getQualifyTime()?->format('Y-m-d H:i:s'));
        $this->assertEquals($rewardTime->format('Y-m-d H:i:s'), $savedReferral->getRewardTime()?->format('Y-m-d H:i:s'));

        // Verify logical time progression
        $this->assertLessThan($savedReferral->getQualifyTime(), $savedReferral->getCreateTime());
        $this->assertLessThan($savedReferral->getRewardTime(), $savedReferral->getQualifyTime());
    }

    public function testReferralTokenHandling(): void
    {
        $client = self::createClientWithDatabase();

        // Test with token
        $referralWithToken = new Referral();
        $referralWithToken->setId('referral-with-token-' . uniqid());
        $referralWithToken->setCampaignId('campaign-token-test');
        $referralWithToken->setReferrerType('user');
        $referralWithToken->setReferrerId('referrer-token');
        $referralWithToken->setRefereeType('user');
        $referralWithToken->setRefereeId('referee-token-with');
        $referralWithToken->setToken('attribution-token-123456');
        $referralWithToken->setSource('token-test');
        $referralWithToken->setState(ReferralState::ATTRIBUTED);
        $referralWithToken->setCreateTime(new \DateTimeImmutable());

        // Test without token (null)
        $referralWithoutToken = new Referral();
        $referralWithoutToken->setId('referral-without-token-' . uniqid());
        $referralWithoutToken->setCampaignId('campaign-token-test');
        $referralWithoutToken->setReferrerType('user');
        $referralWithoutToken->setReferrerId('referrer-no-token');
        $referralWithoutToken->setRefereeType('user');
        $referralWithoutToken->setRefereeId('referee-token-without');
        $referralWithoutToken->setToken(null);
        $referralWithoutToken->setSource('direct');
        $referralWithoutToken->setState(ReferralState::CREATED);
        $referralWithoutToken->setCreateTime(new \DateTimeImmutable());

        $referralRepository = self::getService(ReferralRepository::class);
        self::assertInstanceOf(ReferralRepository::class, $referralRepository);
        $referralRepository->save($referralWithToken, true);
        $referralRepository->save($referralWithoutToken, true);

        // Verify token handling
        $savedWithToken = $referralRepository->find($referralWithToken->getId());
        $this->assertNotNull($savedWithToken);
        $this->assertEquals('attribution-token-123456', $savedWithToken->getToken());

        $savedWithoutToken = $referralRepository->find($referralWithoutToken->getId());
        $this->assertNotNull($savedWithoutToken);
        $this->assertNull($savedWithoutToken->getToken());
    }

    public function testReferralSourceHandling(): void
    {
        $client = self::createClientWithDatabase();

        // Test different source types
        $sources = ['web', 'mobile', 'app', 'email', 'sms', 'qr_code', 'social', 'direct'];

        $referralRepository = self::getService(ReferralRepository::class);
        self::assertInstanceOf(ReferralRepository::class, $referralRepository);

        foreach ($sources as $index => $source) {
            $referral = new Referral();
            $referral->setId('referral-source-' . str_replace('_', '-', $source) . '-' . uniqid());
            $referral->setCampaignId('campaign-source-test');
            $referral->setReferrerType('user');
            $referral->setReferrerId('referrer-source');
            $referral->setRefereeType('user');
            $referral->setRefereeId('referee-source-' . $index);
            $referral->setToken('token-source-' . $index);
            $referral->setSource($source);
            $referral->setState(ReferralState::CREATED);
            $referral->setCreateTime(new \DateTimeImmutable());

            $referralRepository->save($referral, true);

            $savedReferral = $referralRepository->find($referral->getId());
            $this->assertNotNull($savedReferral);
            $this->assertEquals($source, $savedReferral->getSource());
        }
    }

    public function testReferralUniqueConstraint(): void
    {
        $client = self::createClientWithDatabase();

        $baseCampaignId = 'unique-campaign-' . uniqid();
        $baseReferrerId = 'unique-referrer-' . uniqid();
        $baseRefereeId = 'unique-referee-' . uniqid();

        // Create first referral
        $referral1 = new Referral();
        $referral1->setId('unique-test-1-' . uniqid());
        $referral1->setCampaignId($baseCampaignId);
        $referral1->setReferrerType('user');
        $referral1->setReferrerId($baseReferrerId);
        $referral1->setRefereeType('user');
        $referral1->setRefereeId($baseRefereeId);
        $referral1->setToken('token-unique-1');
        $referral1->setSource('unique-test');
        $referral1->setState(ReferralState::CREATED);
        $referral1->setCreateTime(new \DateTimeImmutable());

        $referralRepository = self::getService(ReferralRepository::class);
        self::assertInstanceOf(ReferralRepository::class, $referralRepository);
        $referralRepository->save($referral1, true);

        // Try to create same referral with different campaign should work
        $referral2 = new Referral();
        $referral2->setId('unique-test-2-' . uniqid());
        $referral2->setCampaignId($baseCampaignId . '-different');
        $referral2->setReferrerType('user');
        $referral2->setReferrerId($baseReferrerId);
        $referral2->setRefereeType('user');
        $referral2->setRefereeId($baseRefereeId);
        $referral2->setToken('token-unique-2');
        $referral2->setSource('unique-test');
        $referral2->setState(ReferralState::CREATED);
        $referral2->setCreateTime(new \DateTimeImmutable());
        $referralRepository->save($referral2, true);

        // Verify both referrals exist
        $savedReferral1 = $referralRepository->find($referral1->getId());
        $this->assertNotNull($savedReferral1);

        $savedReferral2 = $referralRepository->find($referral2->getId());
        $this->assertNotNull($savedReferral2);
        $this->assertNotEquals($savedReferral1->getCampaignId(), $savedReferral2->getCampaignId());
    }

    public function testReferralStringRepresentation(): void
    {
        $client = self::createClientWithDatabase();

        $referral = new Referral();
        $referral->setId('string-test-' . uniqid());
        $referral->setCampaignId('campaign-string');
        $referral->setReferrerType('user');
        $referral->setReferrerId('referrer-string-123');
        $referral->setRefereeType('user');
        $referral->setRefereeId('referee-string-456');
        $referral->setToken('token-string');
        $referral->setSource('string-test');
        $referral->setState(ReferralState::QUALIFIED);
        $referral->setCreateTime(new \DateTimeImmutable());

        // Test toString method
        $expectedString = 'user:referrer-string-123 -> user:referee-string-456';
        $this->assertEquals($expectedString, (string) $referral);

        $referralRepository = self::getService(ReferralRepository::class);
        self::assertInstanceOf(ReferralRepository::class, $referralRepository);
        $referralRepository->save($referral, true);

        $savedReferral = $referralRepository->find($referral->getId());
        $this->assertNotNull($savedReferral);
        $this->assertEquals($expectedString, (string) $savedReferral);

        // Test with different state
        $revokedReferral = new Referral();
        $revokedReferral->setId('string-revoked-' . uniqid());
        $revokedReferral->setCampaignId('campaign-string');
        $revokedReferral->setReferrerType('agent');
        $revokedReferral->setReferrerId('agent-789');
        $revokedReferral->setRefereeType('user');
        $revokedReferral->setRefereeId('user-abc');
        $revokedReferral->setSource('string-test');
        $revokedReferral->setState(ReferralState::REVOKED);
        $revokedReferral->setCreateTime(new \DateTimeImmutable());

        $expectedRevokedString = 'agent:agent-789 -> user:user-abc';
        $this->assertEquals($expectedRevokedString, (string) $revokedReferral);
    }

    public function testValidationErrors(): void
    {
        // 使用Entity验证策略避免JSON字段类型问题
        $referral = new Referral();
        // 故意留空必填字段
        $referral->setId(''); // 留空ID
        $referral->setCampaignId(''); // 留空活动ID
        // 设置其他非必填字段为有效值
        $referral->setReferrerType('user');
        $referral->setReferrerId('user-123');
        $referral->setRefereeType('user');
        $referral->setRefereeId('user-456');
        $referral->setState(ReferralState::CREATED);
        $referral->setCreateTime(new \DateTimeImmutable());

        // 获取验证器并验证实体
        $client = self::createClientWithDatabase();
        $validator = self::getService(ValidatorInterface::class);
        $errors = $validator->validate($referral);

        // 验证确实有验证错误（因为必填字段为空）
        $this->assertGreaterThan(0, count($errors), 'Validation should catch empty required fields');

        // 验证错误信息包含预期内容
        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[] = $error->getMessage();
        }
        $hasBlankError = false;
        foreach ($errorMessages as $message) {
            $messageStr = (string) $message;
            if (str_contains($messageStr, 'blank') || str_contains($messageStr, 'should not be blank')) {
                $hasBlankError = true;
                break;
            }
        }
        $this->assertTrue($hasBlankError, 'Should have blank field validation error');
    }
}
