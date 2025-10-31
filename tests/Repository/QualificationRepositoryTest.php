<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\MgmCoreBundle\Entity\Qualification;
use Tourze\MgmCoreBundle\Enum\Decision;
use Tourze\MgmCoreBundle\Repository\QualificationRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(QualificationRepository::class)]
#[RunTestsInSeparateProcesses]
class QualificationRepositoryTest extends AbstractRepositoryTestCase
{
    protected function onSetUp(): void
    {
    }

    protected function createNewEntity(): object
    {
        $q = new Qualification();
        $q->setId('test-qualification-' . uniqid());
        $q->setReferralId('ref-' . uniqid());
        $q->setDecision(Decision::QUALIFIED);
        $q->setReason('init');
        $q->setEvidenceJson([]);
        $q->setOccurTime(new \DateTimeImmutable());
        $q->setCreateTime(new \DateTimeImmutable());

        return $q;
    }

    protected function getRepository(): QualificationRepository
    {
        $repository = self::getContainer()->get(QualificationRepository::class);
        self::assertInstanceOf(QualificationRepository::class, $repository);

        return $repository;
    }

    public function testSave(): void
    {
        $qualification = $this->createTestQualification();

        $this->getRepository()->save($qualification, true);

        $found = $this->getRepository()->find('qualification-1');
        $this->assertNotNull($found);
        $this->assertSame('qualification-1', $found->getId());
        $this->assertSame('referral-123', $found->getReferralId());
        $this->assertSame(Decision::QUALIFIED, $found->getDecision());
        $this->assertSame('Meets all requirements', $found->getReason());
        $this->assertSame(['order_amount' => 100.50, 'category' => 'electronics'], $found->getEvidenceJson());
    }

    public function testSaveWithoutFlush(): void
    {
        $qualification = $this->createTestQualification('qualification-no-flush');

        $this->getRepository()->save($qualification, false);

        // 手动flush以验证数据已持久化
        self::getEntityManager()->flush();

        $found = $this->getRepository()->find('qualification-no-flush');
        $this->assertNotNull($found);
        $this->assertSame('referral-123', $found->getReferralId());
    }

    public function testRemove(): void
    {
        $qualification = $this->createTestQualification('qualification-to-remove');
        $this->getRepository()->save($qualification, true);

        $this->getRepository()->remove($qualification, true);

        $found = $this->getRepository()->find('qualification-to-remove');
        $this->assertNull($found);
    }

    // testRemoveWithoutFlush() 由基类提供

    public function testFindByReferralId(): void
    {
        // 创建多个与同一推荐相关的资格审核记录
        $qualification1 = $this->createTestQualification('qualification-1');
        $qualification1->setReferralId('referral-match');
        $qualification1->setDecision(Decision::QUALIFIED);
        $this->getRepository()->save($qualification1, true);

        $qualification2 = $this->createTestQualification('qualification-2');
        $qualification2->setReferralId('referral-match');
        $qualification2->setDecision(Decision::REJECTED);
        $qualification2->setReason('Insufficient order amount');
        $this->getRepository()->save($qualification2, true);

        // 创建不匹配的记录
        $qualification3 = $this->createTestQualification('qualification-3');
        $qualification3->setReferralId('referral-different');
        $this->getRepository()->save($qualification3, true);

        // 查找匹配的记录
        $results = $this->getRepository()->findByReferralId('referral-match');
        $this->assertCount(2, $results);

        $qualificationIds = array_map(fn (Qualification $q) => $q->getId(), $results);
        $this->assertContains('qualification-1', $qualificationIds);
        $this->assertContains('qualification-2', $qualificationIds);

        // 查找不匹配的条件
        $results = $this->getRepository()->findByReferralId('referral-nonexistent');
        $this->assertCount(0, $results);
    }

    public function testFindLatestByReferralId(): void
    {
        $referralId = 'referral-latest-test';
        $baseTime = new \DateTimeImmutable('2023-12-01 10:00:00');

        // 创建多个不同时间的资格审核记录
        $qualification1 = $this->createTestQualification('qualification-early');
        $qualification1->setReferralId($referralId);
        $qualification1->setOccurTime(clone $baseTime);
        $this->getRepository()->save($qualification1, true);

        $qualification2 = $this->createTestQualification('qualification-middle');
        $qualification2->setReferralId($referralId);
        $qualification2->setOccurTime((clone $baseTime)->modify('+1 hour'));
        $this->getRepository()->save($qualification2, true);

        $qualification3 = $this->createTestQualification('qualification-latest');
        $qualification3->setReferralId($referralId);
        $qualification3->setOccurTime((clone $baseTime)->modify('+2 hours'));
        $qualification3->setDecision(Decision::REJECTED);
        $this->getRepository()->save($qualification3, true);

        // 查找最新的记录
        $latest = $this->getRepository()->findLatestByReferralId($referralId);
        $this->assertNotNull($latest);
        $this->assertSame('qualification-latest', $latest->getId());
        $this->assertSame(Decision::REJECTED, $latest->getDecision());

        // 查找不存在的推荐ID
        $result = $this->getRepository()->findLatestByReferralId('referral-nonexistent');
        $this->assertNull($result);
    }

    public function testFindLatestByReferralIdWithSingleRecord(): void
    {
        $qualification = $this->createTestQualification('qualification-single');
        $qualification->setReferralId('referral-single');
        $this->getRepository()->save($qualification, true);

        $latest = $this->getRepository()->findLatestByReferralId('referral-single');
        $this->assertNotNull($latest);
        $this->assertSame('qualification-single', $latest->getId());
    }

    public function testCountByDecision(): void
    {
        // 清理已存在的数据
        self::getEntityManager()->createQuery('DELETE FROM ' . Qualification::class)->execute();

        // 创建多个不同决定的资格审核记录
        $qualifiedCount = 3;
        $rejectedCount = 2;

        for ($i = 1; $i <= $qualifiedCount; ++$i) {
            $qualification = $this->createTestQualification("qualified-{$i}");
            $qualification->setDecision(Decision::QUALIFIED);
            $this->getRepository()->save($qualification, true);
        }

        for ($i = 1; $i <= $rejectedCount; ++$i) {
            $qualification = $this->createTestQualification("rejected-{$i}");
            $qualification->setDecision(Decision::REJECTED);
            $this->getRepository()->save($qualification, true);
        }

        // 测试计数
        $qualifiedActualCount = $this->getRepository()->countByDecision(Decision::QUALIFIED);
        $this->assertSame($qualifiedCount, $qualifiedActualCount);

        $rejectedActualCount = $this->getRepository()->countByDecision(Decision::REJECTED);
        $this->assertSame($rejectedCount, $rejectedActualCount);
    }

    public function testCountByDecisionWithNoResults(): void
    {
        // 清理已存在的数据
        self::getEntityManager()->createQuery('DELETE FROM ' . Qualification::class)->execute();

        // 清空数据，测试空结果
        $count = $this->getRepository()->countByDecision(Decision::QUALIFIED);
        $this->assertSame(0, $count);

        $count = $this->getRepository()->countByDecision(Decision::REJECTED);
        $this->assertSame(0, $count);
    }

    public function testQualificationWithComplexEvidenceJson(): void
    {
        $complexEvidence = [
            'user_info' => [
                'user_id' => 'user-123',
                'registration_date' => '2023-11-01',
                'verified' => true,
            ],
            'order_details' => [
                ['order_id' => 'order-1', 'amount' => 150.75, 'category' => 'electronics'],
                ['order_id' => 'order-2', 'amount' => 89.99, 'category' => 'books'],
            ],
            'qualification_criteria' => [
                'min_order_amount' => 100.0,
                'allowed_categories' => ['electronics', 'books', 'clothing'],
            ],
        ];

        $qualification = $this->createTestQualification('qualification-complex-evidence');
        $qualification->setEvidenceJson($complexEvidence);
        $this->getRepository()->save($qualification, true);

        $found = $this->getRepository()->find('qualification-complex-evidence');
        $this->assertNotNull($found);

        $evidence = $found->getEvidenceJson();
        $this->assertIsArray($evidence);
        $this->assertArrayHasKey('user_info', $evidence);
        $this->assertArrayHasKey('order_details', $evidence);
        $this->assertTrue($evidence['user_info']['verified']);
        $this->assertCount(2, $evidence['order_details']);
        $this->assertSame(150.75, $evidence['order_details'][0]['amount']);
    }

    public function testQualificationWithEmptyEvidenceJson(): void
    {
        $qualification = $this->createTestQualification('qualification-empty-evidence');
        $qualification->setEvidenceJson([]);
        $this->getRepository()->save($qualification, true);

        $found = $this->getRepository()->find('qualification-empty-evidence');
        $this->assertNotNull($found);
        $this->assertSame([], $found->getEvidenceJson());
    }

    public function testQualificationWithNullReason(): void
    {
        $qualification = $this->createTestQualification('qualification-null-reason');
        $qualification->setReason(null);
        $this->getRepository()->save($qualification, true);

        $found = $this->getRepository()->find('qualification-null-reason');
        $this->assertNotNull($found);
        $this->assertNull($found->getReason());
    }

    public function testQualificationWithEmptyReason(): void
    {
        $qualification = $this->createTestQualification('qualification-empty-reason');
        $qualification->setReason('');
        $this->getRepository()->save($qualification, true);

        $found = $this->getRepository()->find('qualification-empty-reason');
        $this->assertNotNull($found);
        $this->assertSame('', $found->getReason());
    }

    public function testQualificationWithDifferentDecisions(): void
    {
        // 测试合格决定
        $qualifiedQualification = $this->createTestQualification('qualified-qualification');
        $qualifiedQualification->setDecision(Decision::QUALIFIED);
        $qualifiedQualification->setReason('All criteria met');
        $this->getRepository()->save($qualifiedQualification, true);

        // 测试拒绝决定
        $rejectedQualification = $this->createTestQualification('rejected-qualification');
        $rejectedQualification->setDecision(Decision::REJECTED);
        $rejectedQualification->setReason('Insufficient order amount');
        $this->getRepository()->save($rejectedQualification, true);

        $foundQualified = $this->getRepository()->find('qualified-qualification');
        $this->assertNotNull($foundQualified);
        $this->assertSame(Decision::QUALIFIED, $foundQualified->getDecision());
        $this->assertSame('All criteria met', $foundQualified->getReason());

        $foundRejected = $this->getRepository()->find('rejected-qualification');
        $this->assertNotNull($foundRejected);
        $this->assertSame(Decision::REJECTED, $foundRejected->getDecision());
        $this->assertSame('Insufficient order amount', $foundRejected->getReason());
    }

    public function testQualificationTimeStamps(): void
    {
        $occurTime = new \DateTimeImmutable('2023-12-01 15:30:45');
        $createTime = new \DateTimeImmutable('2023-12-01 15:35:00');

        $qualification = $this->createTestQualification('qualification-timestamps');
        $qualification->setOccurTime($occurTime);
        $qualification->setCreateTime($createTime);
        $this->getRepository()->save($qualification, true);

        $found = $this->getRepository()->find('qualification-timestamps');
        $this->assertNotNull($found);
        $this->assertEquals($occurTime, $found->getOccurTime());
        $this->assertEquals($createTime, $found->getCreateTime());
    }

    public function testQualificationStringRepresentation(): void
    {
        $qualification = $this->createTestQualification('qualification-string');
        $qualification->setReferralId('referral-456');
        $qualification->setDecision(Decision::REJECTED);

        $this->assertSame('referral-456 - rejected', (string) $qualification);

        // 测试另一个决定
        $qualification->setDecision(Decision::QUALIFIED);
        $this->assertSame('referral-456 - qualified', (string) $qualification);
    }

    public function testMultipleQualificationHistoryForReferral(): void
    {
        $referralId = 'referral-history-test';
        $baseTime = new \DateTimeImmutable('2023-12-01 10:00:00');

        // 模拟资格审核历史：初始拒绝，后来合格
        $initialRejection = $this->createTestQualification('initial-rejection');
        $initialRejection->setReferralId($referralId);
        $initialRejection->setDecision(Decision::REJECTED);
        $initialRejection->setReason('Order amount too low');
        $initialRejection->setOccurTime(clone $baseTime);
        $this->getRepository()->save($initialRejection, true);

        $laterQualification = $this->createTestQualification('later-qualification');
        $laterQualification->setReferralId($referralId);
        $laterQualification->setDecision(Decision::QUALIFIED);
        $laterQualification->setReason('Additional qualifying purchase made');
        $laterQualification->setOccurTime((clone $baseTime)->modify('+1 day'));
        $this->getRepository()->save($laterQualification, true);

        // 验证历史记录
        $history = $this->getRepository()->findByReferralId($referralId);
        $this->assertCount(2, $history);

        // 验证最新记录
        $latest = $this->getRepository()->findLatestByReferralId($referralId);
        $this->assertNotNull($latest);
        $this->assertSame('later-qualification', $latest->getId());
        $this->assertSame(Decision::QUALIFIED, $latest->getDecision());
    }

    private function createTestQualification(string $id = 'qualification-1'): Qualification
    {
        $qualification = new Qualification();
        $qualification->setId($id);
        $qualification->setReferralId('referral-123');
        $qualification->setDecision(Decision::QUALIFIED);
        $qualification->setReason('Meets all requirements');
        $qualification->setEvidenceJson(['order_amount' => 100.50, 'category' => 'electronics']);
        $qualification->setOccurTime(new \DateTimeImmutable());
        $qualification->setCreateTime(new \DateTimeImmutable());

        return $qualification;
    }
}
