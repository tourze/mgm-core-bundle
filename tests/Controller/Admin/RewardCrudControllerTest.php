<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\Controller\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\MgmCoreBundle\Controller\Admin\RewardCrudController;
use Tourze\MgmCoreBundle\Entity\Reward;
use Tourze\MgmCoreBundle\Enum\Beneficiary;
use Tourze\MgmCoreBundle\Enum\RewardState;
use Tourze\MgmCoreBundle\Repository\RewardRepository;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(RewardCrudController::class)]
#[RunTestsInSeparateProcesses]
final class RewardCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getControllerService(): RewardCrudController
    {
        return self::getService(RewardCrudController::class);
    }

    /**
     * 提供索引页面表头
     *
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'reward_id_header' => ['奖励ID'];
        yield 'referral_id_header' => ['推荐关系ID'];
        yield 'beneficiary_header' => ['受益人类型'];
        yield 'reward_type_header' => ['奖励类型'];
        yield 'reward_state_header' => ['奖励状态'];
        yield 'external_issue_id_header' => ['外部发放ID'];
        yield 'create_time_header' => ['创建时间'];
        yield 'grant_time_header' => ['发放时间'];
    }

    /**
     * 提供新建页面字段
     *
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'id_field' => ['id'];
        yield 'referralId_field' => ['referralId'];
        yield 'beneficiary_field' => ['beneficiary'];
        yield 'type_field' => ['type'];
        yield 'specJson_field' => ['specJson'];
        yield 'state_field' => ['state'];
        yield 'externalIssueId_field' => ['externalIssueId'];
        yield 'idemKey_field' => ['idemKey'];
        yield 'createTime_field' => ['createTime'];
        yield 'grantTime_field' => ['grantTime'];
        yield 'revokeTime_field' => ['revokeTime'];
    }

    /**
     * 提供编辑页面字段
     *
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'edit_id_field' => ['id'];
        yield 'edit_referralId_field' => ['referralId'];
        yield 'edit_beneficiary_field' => ['beneficiary'];
        yield 'edit_type_field' => ['type'];
        yield 'edit_specJson_field' => ['specJson'];
        yield 'edit_state_field' => ['state'];
        yield 'edit_externalIssueId_field' => ['externalIssueId'];
        yield 'edit_idemKey_field' => ['idemKey'];
        yield 'edit_createTime_field' => ['createTime'];
        yield 'edit_grantTime_field' => ['grantTime'];
        yield 'edit_revokeTime_field' => ['revokeTime'];
    }

    public function testIndexPage(): void
    {
        $client = self::createAuthenticatedClient();
        $crawler = $client->request('GET', '/admin');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Navigate to Reward CRUD
        $link = $crawler->filter('a[href*="RewardCrudController"]')->first();
        if ($link->count() > 0) {
            $client->click($link->link());
            $this->assertEquals(200, $client->getResponse()->getStatusCode());
        }
    }

    public function testCreateReward(): void
    {
        $client = self::createAuthenticatedClient();
        $client->request('GET', '/admin');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Test entity creation and persistence
        $reward = new Reward();
        $reward->setId('reward-test-' . uniqid());
        $reward->setReferralId('referral-123');
        $reward->setBeneficiary(Beneficiary::REFERRER);
        $reward->setBeneficiaryType('user');
        $reward->setBeneficiaryId('user-456');
        $reward->setState(RewardState::PENDING);
        $reward->setType('points');
        $reward->setSpecJson([
            'amount' => 100,
            'description' => '推荐奖励',
        ]);
        $reward->setIdemKey('idem-test-' . uniqid());
        $reward->setCreateTime(new \DateTimeImmutable());

        $rewardRepository = self::getService(RewardRepository::class);
        self::assertInstanceOf(RewardRepository::class, $rewardRepository);
        $rewardRepository->save($reward, true);

        // Verify reward was created
        $savedReward = self::getEntityManager()->getRepository(Reward::class)->find($reward->getId());
        $this->assertNotNull($savedReward);
        $this->assertEquals('referral-123', $savedReward->getReferralId());
        $this->assertEquals(Beneficiary::REFERRER, $savedReward->getBeneficiary());
        $this->assertEquals('user', $savedReward->getBeneficiaryType());
        $this->assertEquals('user-456', $savedReward->getBeneficiaryId());
        $this->assertEquals(RewardState::PENDING, $savedReward->getState());
        $this->assertIsArray($savedReward->getSpecJson());
        $this->assertArrayHasKey('amount', $savedReward->getSpecJson());
        $this->assertEquals(100, $savedReward->getSpecJson()['amount']);
    }

    public function testRewardDataPersistence(): void
    {
        // Create client to initialize database
        $client = self::createClientWithDatabase();

        // Create test rewards with different beneficiaries
        $reward1 = new Reward();
        $reward1->setId('reward-referrer-' . uniqid());
        $reward1->setReferralId('referral-beneficiary-test');
        $reward1->setBeneficiary(Beneficiary::REFERRER);
        $reward1->setBeneficiaryType('user');
        $reward1->setBeneficiaryId('referrer-001');
        $reward1->setState(RewardState::GRANTED);
        $reward1->setType('cash');
        $reward1->setSpecJson([
            'type' => 'cash',
            'amount' => 50.00,
            'currency' => 'CNY',
        ]);
        $reward1->setIdemKey('idem-referrer-' . uniqid());
        $reward1->setCreateTime(new \DateTimeImmutable());
        $reward1->setGrantTime(new \DateTimeImmutable());

        $rewardRepository = self::getService(RewardRepository::class);
        self::assertInstanceOf(RewardRepository::class, $rewardRepository);
        $rewardRepository->save($reward1, true);

        $reward2 = new Reward();
        $reward2->setId('reward-referee-' . uniqid());
        $reward2->setReferralId('referral-beneficiary-test');
        $reward2->setBeneficiary(Beneficiary::REFEREE);
        $reward2->setBeneficiaryType('user');
        $reward2->setBeneficiaryId('referee-001');
        $reward2->setState(RewardState::PENDING);
        $reward2->setType('discount');
        $reward2->setSpecJson([
            'percentage' => 20,
            'max_amount' => 100.00,
        ]);
        $reward2->setIdemKey('idem-referee-' . uniqid());
        $reward2->setCreateTime(new \DateTimeImmutable());
        $rewardRepository->save($reward2, true);

        // Verify rewards are saved correctly
        $savedReward1 = $rewardRepository->find($reward1->getId());
        $this->assertNotNull($savedReward1);
        $this->assertEquals('referral-beneficiary-test', $savedReward1->getReferralId());
        $this->assertEquals(Beneficiary::REFERRER, $savedReward1->getBeneficiary());
        $this->assertEquals('referrer-001', $savedReward1->getBeneficiaryId());
        $this->assertEquals(RewardState::GRANTED, $savedReward1->getState());
        $this->assertEquals('cash', $savedReward1->getSpecJson()['type']);
        $this->assertNotNull($savedReward1->getGrantTime());

        $savedReward2 = $rewardRepository->find($reward2->getId());
        $this->assertNotNull($savedReward2);
        $this->assertEquals(Beneficiary::REFEREE, $savedReward2->getBeneficiary());
        $this->assertEquals('referee-001', $savedReward2->getBeneficiaryId());
        $this->assertEquals(RewardState::PENDING, $savedReward2->getState());
        $this->assertEquals('discount', $savedReward2->getType());
        $this->assertNull($savedReward2->getGrantTime());
    }

    public function testRewardStateHandling(): void
    {
        $client = self::createClientWithDatabase();

        // Test different reward states
        $states = [
            RewardState::PENDING,
            RewardState::GRANTED,
            RewardState::CANCELLED,
        ];

        $rewardRepository = self::getService(RewardRepository::class);
        self::assertInstanceOf(RewardRepository::class, $rewardRepository);

        foreach ($states as $index => $state) {
            $reward = new Reward();
            $reward->setId('reward-state-' . $state->value . '-' . uniqid());
            $reward->setReferralId('referral-state-test');
            $reward->setBeneficiary(Beneficiary::REFERRER);
            $reward->setBeneficiaryType('user');
            $reward->setBeneficiaryId('user-state-' . $index);
            $reward->setState($state);
            $reward->setType('points');
            $reward->setSpecJson([
                'amount' => 100 * ($index + 1),
                'state_test' => true,
            ]);
            $reward->setIdemKey('idem-state-' . $state->value . '-' . $index);
            $reward->setCreateTime(new \DateTimeImmutable());

            // Set grant time for granted/cancelled states
            if (RewardState::GRANTED === $state || RewardState::CANCELLED === $state) {
                $reward->setGrantTime(new \DateTimeImmutable());
            }

            $rewardRepository->save($reward, true);

            $savedReward = $rewardRepository->find($reward->getId());
            $this->assertNotNull($savedReward);
            $this->assertEquals($state, $savedReward->getState());
            $this->assertEquals($state->value, $savedReward->getState()->value);
            $this->assertEquals($state->getLabel(), $savedReward->getState()->getLabel());

            if (RewardState::PENDING === $state) {
                $this->assertNull($savedReward->getGrantTime());
            } else {
                $this->assertNotNull($savedReward->getGrantTime());
            }
        }
    }

    public function testRewardBeneficiaryHandling(): void
    {
        $client = self::createClientWithDatabase();

        // Test REFERRER beneficiary
        $referrerReward = new Reward();
        $referrerReward->setId('reward-referrer-ben-' . uniqid());
        $referrerReward->setReferralId('referral-beneficiary-specific');
        $referrerReward->setBeneficiary(Beneficiary::REFERRER);
        $referrerReward->setBeneficiaryType('user');
        $referrerReward->setBeneficiaryId('referrer-specific');
        $referrerReward->setState(RewardState::GRANTED);
        $referrerReward->setType('cash');
        $referrerReward->setSpecJson(['for' => 'referrer']);
        $referrerReward->setIdemKey('idem-referrer-ben-' . uniqid());
        $referrerReward->setCreateTime(new \DateTimeImmutable());

        // Test REFEREE beneficiary
        $refereeReward = new Reward();
        $refereeReward->setId('reward-referee-ben-' . uniqid());
        $refereeReward->setReferralId('referral-beneficiary-specific');
        $refereeReward->setBeneficiary(Beneficiary::REFEREE);
        $refereeReward->setBeneficiaryType('user');
        $refereeReward->setBeneficiaryId('referee-specific');
        $refereeReward->setState(RewardState::PENDING);
        $refereeReward->setType('discount');
        $refereeReward->setSpecJson(['for' => 'referee']);
        $refereeReward->setIdemKey('idem-referee-ben-' . uniqid());
        $refereeReward->setCreateTime(new \DateTimeImmutable());

        $rewardRepository = self::getService(RewardRepository::class);
        self::assertInstanceOf(RewardRepository::class, $rewardRepository);
        $rewardRepository->save($referrerReward, true);
        $rewardRepository->save($refereeReward, true);

        // Verify beneficiaries are correctly set
        $savedReferrerReward = $rewardRepository->find($referrerReward->getId());
        $this->assertNotNull($savedReferrerReward);
        $this->assertEquals(Beneficiary::REFERRER, $savedReferrerReward->getBeneficiary());
        $this->assertEquals('referrer', $savedReferrerReward->getBeneficiary()->value);
        $this->assertEquals('推荐人', $savedReferrerReward->getBeneficiary()->getLabel());

        $savedRefereeReward = $rewardRepository->find($refereeReward->getId());
        $this->assertNotNull($savedRefereeReward);
        $this->assertEquals(Beneficiary::REFEREE, $savedRefereeReward->getBeneficiary());
        $this->assertEquals('referee', $savedRefereeReward->getBeneficiary()->value);
        $this->assertEquals('被推荐人', $savedRefereeReward->getBeneficiary()->getLabel());
    }

    public function testRewardJsonHandling(): void
    {
        $client = self::createClientWithDatabase();

        // Test with complex reward JSON
        $reward = new Reward();
        $reward->setId('reward-json-complex-' . uniqid());
        $reward->setReferralId('referral-json-test');
        $reward->setBeneficiary(Beneficiary::REFERRER);
        $reward->setBeneficiaryType('user');
        $reward->setBeneficiaryId('user-json-test');
        $reward->setState(RewardState::GRANTED);
        $reward->setType('multi_tier');
        $reward->setSpecJson([
            'cash_component' => [
                'amount' => 100.00,
                'currency' => 'CNY',
                'transfer_method' => 'bank_transfer',
                'account_info' => [
                    'bank_name' => '招商银行',
                    'account_number' => '****1234',
                ],
            ],
            'points_component' => [
                'amount' => 500,
                'point_type' => 'loyalty_points',
                'expiry_date' => '2024-12-31T23:59:59Z',
            ],
            'bonus_items' => [
                'vouchers' => [
                    ['type' => 'discount', 'value' => 20, 'category' => 'electronics'],
                    ['type' => 'cashback', 'value' => 50, 'min_spend' => 200],
                ],
                'privileges' => [
                    'vip_status' => true,
                    'free_shipping' => true,
                    'priority_support' => true,
                ],
            ],
            'metadata' => [
                'campaign_version' => '2.1.0',
                'calculation_method' => 'tier_based',
                'applied_rules' => ['new_user_bonus', 'high_value_referral'],
                'processed_by' => 'automated_system',
                'processing_time_ms' => 1250,
            ],
        ]);
        $reward->setIdemKey('idem-json-complex-' . uniqid());
        $reward->setCreateTime(new \DateTimeImmutable());
        $reward->setGrantTime(new \DateTimeImmutable());

        $rewardRepository = self::getService(RewardRepository::class);
        self::assertInstanceOf(RewardRepository::class, $rewardRepository);
        $rewardRepository->save($reward, true);

        $savedReward = $rewardRepository->find($reward->getId());
        $this->assertNotNull($savedReward);

        $rewardData = $savedReward->getSpecJson();
        $this->assertIsArray($rewardData);

        // Test cash component
        $this->assertArrayHasKey('cash_component', $rewardData);
        $this->assertIsArray($rewardData['cash_component']);
        $this->assertArrayHasKey('amount', $rewardData['cash_component']);
        $this->assertEquals(100.00, $rewardData['cash_component']['amount']);

        // Test points component
        $this->assertArrayHasKey('points_component', $rewardData);
        $this->assertIsArray($rewardData['points_component']);
        $this->assertArrayHasKey('amount', $rewardData['points_component']);
        $this->assertEquals(500, $rewardData['points_component']['amount']);

        // Test bonus items
        $this->assertArrayHasKey('bonus_items', $rewardData);
        $this->assertIsArray($rewardData['bonus_items']);
        $this->assertArrayHasKey('vouchers', $rewardData['bonus_items']);
        $this->assertIsArray($rewardData['bonus_items']['vouchers']);
        $this->assertCount(2, $rewardData['bonus_items']['vouchers']);

        $this->assertArrayHasKey('privileges', $rewardData['bonus_items']);
        $this->assertIsArray($rewardData['bonus_items']['privileges']);
        $this->assertArrayHasKey('vip_status', $rewardData['bonus_items']['privileges']);
        $this->assertTrue($rewardData['bonus_items']['privileges']['vip_status']);

        // Test metadata
        $this->assertArrayHasKey('metadata', $rewardData);
        $this->assertIsArray($rewardData['metadata']);
        $this->assertArrayHasKey('campaign_version', $rewardData['metadata']);
        $this->assertEquals('2.1.0', $rewardData['metadata']['campaign_version']);

        $this->assertArrayHasKey('applied_rules', $rewardData['metadata']);
        $this->assertIsArray($rewardData['metadata']['applied_rules']);
        $this->assertContains('new_user_bonus', $rewardData['metadata']['applied_rules']);
    }

    public function testRewardTimeHandling(): void
    {
        $client = self::createClientWithDatabase();

        $createTime = new \DateTimeImmutable('2024-01-15 14:00:00');
        $processTime = new \DateTimeImmutable('2024-01-15 15:30:00');

        $reward = new Reward();
        $reward->setId('reward-time-test-' . uniqid());
        $reward->setReferralId('referral-time-test');
        $reward->setBeneficiary(Beneficiary::REFERRER);
        $reward->setBeneficiaryType('user');
        $reward->setBeneficiaryId('user-time-test');
        $reward->setState(RewardState::GRANTED);
        $reward->setType('time_based');
        $reward->setSpecJson(['time_test' => true]);
        $reward->setIdemKey('idem-time-test-' . uniqid());
        $reward->setCreateTime($createTime);
        $reward->setGrantTime($processTime);

        $rewardRepository = self::getService(RewardRepository::class);
        self::assertInstanceOf(RewardRepository::class, $rewardRepository);
        $rewardRepository->save($reward, true);

        $savedReward = $rewardRepository->find($reward->getId());
        $this->assertNotNull($savedReward);
        $this->assertEquals($createTime->format('Y-m-d H:i:s'), $savedReward->getCreateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals($processTime->format('Y-m-d H:i:s'), $savedReward->getGrantTime()?->format('Y-m-d H:i:s'));

        // Verify grant time is after create time
        $this->assertGreaterThan($savedReward->getCreateTime(), $savedReward->getGrantTime());
    }

    public function testRewardBeneficiaryTypeHandling(): void
    {
        $client = self::createClientWithDatabase();

        // Test different beneficiary types
        $beneficiaryTypes = ['user', 'agent', 'merchant', 'organization'];

        $rewardRepository = self::getService(RewardRepository::class);
        self::assertInstanceOf(RewardRepository::class, $rewardRepository);

        foreach ($beneficiaryTypes as $index => $beneficiaryType) {
            $reward = new Reward();
            $reward->setId('reward-ben-type-' . $beneficiaryType . '-' . uniqid());
            $reward->setReferralId('referral-ben-type-test');
            $reward->setBeneficiary(Beneficiary::REFERRER);
            $reward->setBeneficiaryType($beneficiaryType);
            $reward->setBeneficiaryId($beneficiaryType . '-' . $index);
            $reward->setType('points');
            $reward->setState(RewardState::PENDING);
            $reward->setSpecJson(['beneficiary_type' => $beneficiaryType]);
            $reward->setIdemKey('idem-ben-type-' . $beneficiaryType . '-' . $index);
            $reward->setCreateTime(new \DateTimeImmutable());

            $rewardRepository->save($reward, true);

            $savedReward = $rewardRepository->find($reward->getId());
            $this->assertNotNull($savedReward);
            $this->assertEquals($beneficiaryType, $savedReward->getBeneficiaryType());
            $this->assertEquals($beneficiaryType . '-' . $index, $savedReward->getBeneficiaryId());
        }
    }

    public function testRewardStringRepresentation(): void
    {
        $client = self::createClientWithDatabase();

        $reward = new Reward();
        $reward->setId('reward-string-test-' . uniqid());
        $reward->setReferralId('referral-string-123');
        $reward->setBeneficiary(Beneficiary::REFERRER);
        $reward->setBeneficiaryType('user');
        $reward->setBeneficiaryId('user-string-456');
        $reward->setState(RewardState::GRANTED);
        $reward->setType('points');
        $reward->setSpecJson(['amount' => 100]);
        $reward->setIdemKey('idem-string-test-' . uniqid());
        $reward->setCreateTime(new \DateTimeImmutable());

        // Test toString method
        $expectedString = 'referrer - points (granted)';
        $this->assertEquals($expectedString, (string) $reward);

        $rewardRepository = self::getService(RewardRepository::class);
        self::assertInstanceOf(RewardRepository::class, $rewardRepository);
        $rewardRepository->save($reward, true);

        $savedReward = $rewardRepository->find($reward->getId());
        $this->assertNotNull($savedReward);
        $this->assertEquals($expectedString, (string) $savedReward);

        // Test with different beneficiary and state
        $cancelledReward = new Reward();
        $cancelledReward->setId('reward-cancelled-string-' . uniqid());
        $cancelledReward->setReferralId('referral-cancelled-string');
        $cancelledReward->setBeneficiary(Beneficiary::REFEREE);
        $cancelledReward->setBeneficiaryType('agent');
        $cancelledReward->setBeneficiaryId('agent-cancelled-789');
        $cancelledReward->setState(RewardState::CANCELLED);
        $cancelledReward->setType('cancelled');
        $cancelledReward->setSpecJson([]);
        $cancelledReward->setIdemKey('idem-cancelled-' . uniqid());
        $cancelledReward->setCreateTime(new \DateTimeImmutable());

        $expectedCancelledString = 'referee - cancelled (cancelled)';
        $this->assertEquals($expectedCancelledString, (string) $cancelledReward);
    }

    public function testRewardReferralIdIndexing(): void
    {
        $client = self::createClientWithDatabase();

        $referralId = 'indexed-referral-reward-' . uniqid();

        // Create multiple rewards for same referral (referrer and referee)
        $referrerReward = new Reward();
        $referrerReward->setId('reward-referrer-indexed-' . uniqid());
        $referrerReward->setReferralId($referralId);
        $referrerReward->setBeneficiary(Beneficiary::REFERRER);
        $referrerReward->setBeneficiaryType('user');
        $referrerReward->setBeneficiaryId('referrer-indexed');
        $referrerReward->setState(RewardState::GRANTED);
        $referrerReward->setType('referrer_reward');
        $referrerReward->setSpecJson(['purpose' => 'referrer']);
        $referrerReward->setIdemKey('idem-referrer-indexed-' . uniqid());
        $referrerReward->setCreateTime(new \DateTimeImmutable());

        $refereeReward = new Reward();
        $refereeReward->setId('reward-referee-indexed-' . uniqid());
        $refereeReward->setReferralId($referralId);
        $refereeReward->setBeneficiary(Beneficiary::REFEREE);
        $refereeReward->setBeneficiaryType('user');
        $refereeReward->setBeneficiaryId('referee-indexed');
        $refereeReward->setState(RewardState::PENDING);
        $refereeReward->setType('referee_reward');
        $refereeReward->setSpecJson(['purpose' => 'referee']);
        $refereeReward->setIdemKey('idem-referee-indexed-' . uniqid());
        $refereeReward->setCreateTime(new \DateTimeImmutable());

        $rewardRepository = self::getService(RewardRepository::class);
        self::assertInstanceOf(RewardRepository::class, $rewardRepository);
        $rewardRepository->save($referrerReward, true);
        $rewardRepository->save($refereeReward, true);

        // Verify both rewards can be found by referral ID
        $rewardsByReferralId = $rewardRepository->findBy(['referralId' => $referralId]);
        $this->assertCount(2, $rewardsByReferralId);

        $foundRewardIds = array_map(fn ($reward) => $reward->getId(), $rewardsByReferralId);
        $this->assertContains($referrerReward->getId(), $foundRewardIds);
        $this->assertContains($refereeReward->getId(), $foundRewardIds);

        // Verify beneficiaries are different
        $beneficiaries = array_map(fn ($reward) => $reward->getBeneficiary(), $rewardsByReferralId);
        $this->assertContains(Beneficiary::REFERRER, $beneficiaries);
        $this->assertContains(Beneficiary::REFEREE, $beneficiaries);
    }

    public function testValidationErrors(): void
    {
        // 考虑到specJson字段的类型转换复杂性，我们使用Entity验证策略
        $reward = new Reward();
        // 故意留空必填字段
        $reward->setId(''); // 留空ID
        $reward->setReferralId(''); // 留空推荐ID
        // 设置其他非必填字段为有效值
        $reward->setBeneficiary(Beneficiary::REFERRER);
        $reward->setBeneficiaryType('user');
        $reward->setBeneficiaryId('user-123');
        $reward->setType('points');
        $reward->setState(RewardState::PENDING);
        $reward->setSpecJson([]);
        $reward->setIdemKey('test-idem-key');
        $reward->setCreateTime(new \DateTimeImmutable());

        // 获取验证器并验证实体
        $client = self::createClientWithDatabase();
        $validator = self::getService(ValidatorInterface::class);
        $errors = $validator->validate($reward);

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
