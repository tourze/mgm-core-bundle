<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\Controller\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\MgmCoreBundle\Controller\Admin\QualificationCrudController;
use Tourze\MgmCoreBundle\Entity\Qualification;
use Tourze\MgmCoreBundle\Enum\Decision;
use Tourze\MgmCoreBundle\Repository\QualificationRepository;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(QualificationCrudController::class)]
#[RunTestsInSeparateProcesses]
final class QualificationCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getControllerService(): QualificationCrudController
    {
        return self::getService(QualificationCrudController::class);
    }

    /**
     * 提供索引页面表头
     *
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'qualification_id_header' => ['资格审核ID'];
        yield 'referral_id_header' => ['推荐关系ID'];
        yield 'decision_header' => ['审核决定'];
        yield 'reason_header' => ['审核原因'];
        yield 'occur_time_header' => ['事件发生时间'];
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
        yield 'referralId_field' => ['referralId'];
        yield 'decision_field' => ['decision'];
        yield 'reason_field' => ['reason'];
        yield 'evidenceJson_field' => ['evidenceJson'];
        yield 'occurTime_field' => ['occurTime'];
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
        yield 'edit_decision_field' => ['decision'];
        yield 'edit_reason_field' => ['reason'];
        yield 'edit_evidenceJson_field' => ['evidenceJson'];
        yield 'edit_occurTime_field' => ['occurTime'];
    }

    public function testIndexPage(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);
        $crawler = $client->request('GET', '/admin');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Navigate to Qualification CRUD
        $link = $crawler->filter('a[href*="QualificationCrudController"]')->first();
        if ($link->count() > 0) {
            $client->click($link->link());
            $this->assertEquals(200, $client->getResponse()->getStatusCode());
        }
    }

    public function testCreateQualification(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);
        $client->request('GET', '/admin');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Test entity creation and persistence
        $qualification = new Qualification();
        $qualification->setId('qualification-test-' . uniqid());
        $qualification->setReferralId('referral-123');
        $qualification->setDecision(Decision::QUALIFIED);
        $qualification->setReason('用户满足所有推荐条件');
        $qualification->setEvidenceJson([
            'user_age' => 25,
            'account_status' => 'active',
            'purchase_history' => true,
        ]);
        $qualification->setOccurTime(new \DateTimeImmutable('-1 hour'));
        $qualification->setCreateTime(new \DateTimeImmutable());

        $qualificationRepository = self::getService(QualificationRepository::class);
        self::assertInstanceOf(QualificationRepository::class, $qualificationRepository);
        $qualificationRepository->save($qualification, true);

        // Verify qualification was created
        $savedQualification = $qualificationRepository->find($qualification->getId());
        $this->assertNotNull($savedQualification);
        $this->assertEquals('referral-123', $savedQualification->getReferralId());
        $this->assertEquals(Decision::QUALIFIED, $savedQualification->getDecision());
        $this->assertEquals('用户满足所有推荐条件', $savedQualification->getReason());
        $this->assertArrayHasKey('user_age', $savedQualification->getEvidenceJson());
    }

    public function testQualificationDataPersistence(): void
    {
        // Create client to initialize database
        $client = self::createClientWithDatabase();

        // Create test qualifications with different decisions
        $qualification1 = new Qualification();
        $qualification1->setId('qualification-qualified-' . uniqid());
        $qualification1->setReferralId('referral-qualified-001');
        $qualification1->setDecision(Decision::QUALIFIED);
        $qualification1->setReason('所有验证通过');
        $qualification1->setEvidenceJson([
            'verification_steps' => ['identity', 'email', 'phone'],
            'scores' => ['creditScore' => 85, 'activityScore' => 92],
            'compliance_checks' => ['aml' => true, 'kyc' => true],
        ]);
        $qualification1->setOccurTime(new \DateTimeImmutable('-2 hours'));
        $qualification1->setCreateTime(new \DateTimeImmutable());

        $qualificationRepository = self::getService(QualificationRepository::class);
        self::assertInstanceOf(QualificationRepository::class, $qualificationRepository);
        $qualificationRepository->save($qualification1, true);

        $qualification2 = new Qualification();
        $qualification2->setId('qualification-rejected-' . uniqid());
        $qualification2->setReferralId('referral-rejected-001');
        $qualification2->setDecision(Decision::REJECTED);
        $qualification2->setReason('信用评分不足');
        $qualification2->setEvidenceJson([
            'verification_steps' => ['identity', 'email'],
            'scores' => ['creditScore' => 45, 'activityScore' => 30],
            'failed_checks' => ['insufficient_credit', 'inactive_account'],
        ]);
        $qualification2->setOccurTime(new \DateTimeImmutable('-1 hour'));
        $qualification2->setCreateTime(new \DateTimeImmutable());
        $qualificationRepository->save($qualification2, true);

        // Verify qualifications are saved correctly
        $savedQualification1 = $qualificationRepository->find($qualification1->getId());
        $this->assertNotNull($savedQualification1);
        $this->assertEquals('referral-qualified-001', $savedQualification1->getReferralId());
        $this->assertEquals(Decision::QUALIFIED, $savedQualification1->getDecision());
        $this->assertEquals('所有验证通过', $savedQualification1->getReason());
        $this->assertEquals(85, $savedQualification1->getEvidenceJson()['scores']['creditScore']);

        $savedQualification2 = $qualificationRepository->find($qualification2->getId());
        $this->assertNotNull($savedQualification2);
        $this->assertEquals('referral-rejected-001', $savedQualification2->getReferralId());
        $this->assertEquals(Decision::REJECTED, $savedQualification2->getDecision());
        $this->assertEquals('信用评分不足', $savedQualification2->getReason());
        $this->assertEquals(45, $savedQualification2->getEvidenceJson()['scores']['creditScore']);
    }

    public function testQualificationDecisionHandling(): void
    {
        $client = self::createClientWithDatabase();

        // Test QUALIFIED decision
        $qualifiedQualification = new Qualification();
        $qualifiedQualification->setId('decision-qualified-' . uniqid());
        $qualifiedQualification->setReferralId('decision-test-qualified');
        $qualifiedQualification->setDecision(Decision::QUALIFIED);
        $qualifiedQualification->setReason('通过所有审核标准');
        $qualifiedQualification->setEvidenceJson(['status' => 'approved']);
        $qualifiedQualification->setOccurTime(new \DateTimeImmutable());
        $qualifiedQualification->setCreateTime(new \DateTimeImmutable());

        // Test REJECTED decision
        $rejectedQualification = new Qualification();
        $rejectedQualification->setId('decision-rejected-' . uniqid());
        $rejectedQualification->setReferralId('decision-test-rejected');
        $rejectedQualification->setDecision(Decision::REJECTED);
        $rejectedQualification->setReason('不满足最低要求');
        $rejectedQualification->setEvidenceJson(['status' => 'denied']);
        $rejectedQualification->setOccurTime(new \DateTimeImmutable());
        $rejectedQualification->setCreateTime(new \DateTimeImmutable());

        $qualificationRepository = self::getService(QualificationRepository::class);
        self::assertInstanceOf(QualificationRepository::class, $qualificationRepository);
        $qualificationRepository->save($qualifiedQualification, true);
        $qualificationRepository->save($rejectedQualification, true);

        // Verify decisions are correctly set
        $savedQualified = $qualificationRepository->find($qualifiedQualification->getId());
        $this->assertNotNull($savedQualified);
        $this->assertEquals(Decision::QUALIFIED, $savedQualified->getDecision());
        $this->assertEquals('qualified', $savedQualified->getDecision()->value);
        $this->assertEquals('合格', $savedQualified->getDecision()->getLabel());

        $savedRejected = $qualificationRepository->find($rejectedQualification->getId());
        $this->assertNotNull($savedRejected);
        $this->assertEquals(Decision::REJECTED, $savedRejected->getDecision());
        $this->assertEquals('rejected', $savedRejected->getDecision()->value);
        $this->assertEquals('拒绝', $savedRejected->getDecision()->getLabel());
    }

    public function testQualificationEvidenceJsonHandling(): void
    {
        $client = self::createClientWithDatabase();

        // Test with complex evidence JSON
        $qualification = new Qualification();
        $qualification->setId('evidence-complex-' . uniqid());
        $qualification->setReferralId('evidence-test');
        $qualification->setDecision(Decision::QUALIFIED);
        $qualification->setReason('通过详细审核');
        $qualification->setEvidenceJson([
            'user_profile' => [
                'id' => 'user-12345',
                'name' => '张三',
                'email' => 'zhangsan@example.com',
                'phone' => '+86-13812345678',
                'registration_date' => '2024-01-15T08:30:00Z',
            ],
            'verification_results' => [
                'identity_check' => [
                    'method' => 'id_card',
                    'result' => 'passed',
                    'confidence' => 0.98,
                ],
                'address_verification' => [
                    'method' => 'utility_bill',
                    'result' => 'passed',
                    'confidence' => 0.95,
                ],
                'financial_check' => [
                    'credit_score' => 750,
                    'income_verified' => true,
                    'debt_ratio' => 0.25,
                ],
            ],
            'business_rules' => [
                'age_requirement' => ['min' => 18, 'actual' => 28, 'passed' => true],
                'location_requirement' => ['allowed_regions' => ['CN'], 'user_region' => 'CN', 'passed' => true],
                'activity_requirement' => ['min_transactions' => 5, 'actual_transactions' => 12, 'passed' => true],
            ],
            'metadata' => [
                'processed_at' => '2024-01-15T10:30:00Z',
                'processing_time_ms' => 2500,
                'version' => '1.2.0',
            ],
        ]);
        $qualification->setOccurTime(new \DateTimeImmutable());
        $qualification->setCreateTime(new \DateTimeImmutable());

        $qualificationRepository = self::getService(QualificationRepository::class);
        self::assertInstanceOf(QualificationRepository::class, $qualificationRepository);
        $qualificationRepository->save($qualification, true);

        $savedQualification = $qualificationRepository->find($qualification->getId());
        $this->assertNotNull($savedQualification);

        $evidence = $savedQualification->getEvidenceJson();
        $this->assertIsArray($evidence);
        $this->assertArrayHasKey('user_profile', $evidence);
        $this->assertArrayHasKey('verification_results', $evidence);
        $this->assertArrayHasKey('business_rules', $evidence);

        // Test nested values
        $this->assertEquals('张三', $evidence['user_profile']['name']);
        $this->assertEquals(0.98, $evidence['verification_results']['identity_check']['confidence']);
        $this->assertEquals(750, $evidence['verification_results']['financial_check']['credit_score']);
        $this->assertTrue($evidence['business_rules']['age_requirement']['passed']);
        $this->assertEquals('1.2.0', $evidence['metadata']['version']);
    }

    public function testQualificationReasonHandling(): void
    {
        $client = self::createClientWithDatabase();

        // Test with reason
        $qualificationWithReason = new Qualification();
        $qualificationWithReason->setId('with-reason-' . uniqid());
        $qualificationWithReason->setReferralId('reason-test-with');
        $qualificationWithReason->setDecision(Decision::QUALIFIED);
        $qualificationWithReason->setReason('用户通过了所有必要的验证步骤，包括身份验证、地址验证和财务状况评估');
        $qualificationWithReason->setEvidenceJson(['detailed' => true]);
        $qualificationWithReason->setOccurTime(new \DateTimeImmutable());
        $qualificationWithReason->setCreateTime(new \DateTimeImmutable());

        // Test without reason (null)
        $qualificationWithoutReason = new Qualification();
        $qualificationWithoutReason->setId('without-reason-' . uniqid());
        $qualificationWithoutReason->setReferralId('reason-test-without');
        $qualificationWithoutReason->setDecision(Decision::REJECTED);
        $qualificationWithoutReason->setReason(null);
        $qualificationWithoutReason->setEvidenceJson(['automated' => true]);
        $qualificationWithoutReason->setOccurTime(new \DateTimeImmutable());
        $qualificationWithoutReason->setCreateTime(new \DateTimeImmutable());

        $qualificationRepository = self::getService(QualificationRepository::class);
        self::assertInstanceOf(QualificationRepository::class, $qualificationRepository);
        $qualificationRepository->save($qualificationWithReason, true);
        $qualificationRepository->save($qualificationWithoutReason, true);

        // Verify reason handling
        $savedWithReason = $qualificationRepository->find($qualificationWithReason->getId());
        $this->assertNotNull($savedWithReason);
        $reason = $savedWithReason->getReason();
        $this->assertNotNull($reason, 'Reason should not be null');
        $this->assertStringContainsString('用户通过了所有必要的验证步骤', $reason);

        $savedWithoutReason = $qualificationRepository->find($qualificationWithoutReason->getId());
        $this->assertNotNull($savedWithoutReason);
        $this->assertNull($savedWithoutReason->getReason());
    }

    public function testQualificationStringRepresentation(): void
    {
        $client = self::createClientWithDatabase();

        // Test toString method
        $qualification = new Qualification();
        $qualification->setId('string-test-' . uniqid());
        $qualification->setReferralId('string-referral-123');
        $qualification->setDecision(Decision::QUALIFIED);
        $qualification->setReason('字符串表示测试');
        $qualification->setEvidenceJson(['test' => true]);
        $qualification->setOccurTime(new \DateTimeImmutable());
        $qualification->setCreateTime(new \DateTimeImmutable());

        // Test toString before saving
        $expectedString = 'string-referral-123 - qualified';
        $this->assertEquals($expectedString, (string) $qualification);

        $qualificationRepository = self::getService(QualificationRepository::class);
        self::assertInstanceOf(QualificationRepository::class, $qualificationRepository);
        $qualificationRepository->save($qualification, true);

        $savedQualification = $qualificationRepository->find($qualification->getId());
        $this->assertNotNull($savedQualification);
        $this->assertEquals($expectedString, (string) $savedQualification);

        // Test with REJECTED decision
        $rejectedQualification = new Qualification();
        $rejectedQualification->setId('string-rejected-' . uniqid());
        $rejectedQualification->setReferralId('string-referral-456');
        $rejectedQualification->setDecision(Decision::REJECTED);
        $rejectedQualification->setEvidenceJson([]);
        $rejectedQualification->setOccurTime(new \DateTimeImmutable());
        $rejectedQualification->setCreateTime(new \DateTimeImmutable());

        $expectedRejectedString = 'string-referral-456 - rejected';
        $this->assertEquals($expectedRejectedString, (string) $rejectedQualification);
    }

    public function testQualificationTimeHandling(): void
    {
        $client = self::createClientWithDatabase();

        $occurTime = new \DateTimeImmutable('2024-01-15 14:30:00');
        $createTime = new \DateTimeImmutable('2024-01-15 14:35:00');

        $qualification = new Qualification();
        $qualification->setId('time-test-' . uniqid());
        $qualification->setReferralId('time-test-referral');
        $qualification->setDecision(Decision::QUALIFIED);
        $qualification->setReason('时间测试');
        $qualification->setEvidenceJson(['timestamp_test' => true]);
        $qualification->setOccurTime($occurTime);
        $qualification->setCreateTime($createTime);

        $qualificationRepository = self::getService(QualificationRepository::class);
        self::assertInstanceOf(QualificationRepository::class, $qualificationRepository);
        $qualificationRepository->save($qualification, true);

        $savedQualification = $qualificationRepository->find($qualification->getId());
        $this->assertNotNull($savedQualification);
        $this->assertEquals($occurTime->format('Y-m-d H:i:s'), $savedQualification->getOccurTime()->format('Y-m-d H:i:s'));
        $this->assertEquals($createTime->format('Y-m-d H:i:s'), $savedQualification->getCreateTime()->format('Y-m-d H:i:s'));

        // Verify occur time is before create time (which makes business sense)
        $this->assertLessThan($savedQualification->getCreateTime(), $savedQualification->getOccurTime());
    }

    public function testQualificationReferralIdIndexing(): void
    {
        $client = self::createClientWithDatabase();

        $referralId = 'indexed-referral-' . uniqid();

        // Create multiple qualifications with same referral ID (audit trail)
        $qualification1 = new Qualification();
        $qualification1->setId('qual-indexed-1-' . uniqid());
        $qualification1->setReferralId($referralId);
        $qualification1->setDecision(Decision::QUALIFIED);
        $qualification1->setReason('初次审核通过');
        $qualification1->setEvidenceJson(['round' => 1]);
        $qualification1->setOccurTime(new \DateTimeImmutable('-2 hours'));
        $qualification1->setCreateTime(new \DateTimeImmutable('-2 hours'));

        $qualification2 = new Qualification();
        $qualification2->setId('qual-indexed-2-' . uniqid());
        $qualification2->setReferralId($referralId);
        $qualification2->setDecision(Decision::REJECTED);
        $qualification2->setReason('后续审核发现问题');
        $qualification2->setEvidenceJson(['round' => 2]);
        $qualification2->setOccurTime(new \DateTimeImmutable('-1 hour'));
        $qualification2->setCreateTime(new \DateTimeImmutable('-1 hour'));

        $qualificationRepository = self::getService(QualificationRepository::class);
        self::assertInstanceOf(QualificationRepository::class, $qualificationRepository);
        $qualificationRepository->save($qualification1, true);
        $qualificationRepository->save($qualification2, true);

        // Verify both qualifications can be found by referral ID
        $qualificationsByReferralId = $qualificationRepository->findBy(['referralId' => $referralId]);
        $this->assertCount(2, $qualificationsByReferralId);

        $foundQualificationIds = array_map(fn ($qual) => $qual->getId(), $qualificationsByReferralId);
        $this->assertContains($qualification1->getId(), $foundQualificationIds);
        $this->assertContains($qualification2->getId(), $foundQualificationIds);
    }

    public function testValidationErrors(): void
    {
        // 考虑到evidenceJson字段的类型转换复杂性，我们使用Entity验证策略
        $qualification = new Qualification();
        // 故意留空必填字段
        $qualification->setId(''); // 留空ID
        $qualification->setReferralId(''); // 留空推荐ID
        // 设置其他非必填字段为有效值
        $qualification->setDecision(Decision::QUALIFIED);
        $qualification->setEvidenceJson([]);
        $qualification->setReason('');
        $qualification->setCreateTime(new \DateTimeImmutable());

        // 获取验证器并验证实体
        $client = self::createClientWithDatabase();
        $validator = self::getService(ValidatorInterface::class);
        $errors = $validator->validate($qualification);

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
