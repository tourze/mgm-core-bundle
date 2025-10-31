<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\MgmCoreBundle\Entity\Ledger;
use Tourze\MgmCoreBundle\Enum\Direction;
use Tourze\MgmCoreBundle\Repository\LedgerRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(LedgerRepository::class)]
#[RunTestsInSeparateProcesses]
class LedgerRepositoryTest extends AbstractRepositoryTestCase
{
    protected function onSetUp(): void
    {
    }

    protected function createNewEntity(): object
    {
        $ledger = new Ledger();
        $ledger->setId('zzzz-' . uniqid());
        $ledger->setRewardId('reward-' . uniqid());
        $ledger->setDirection(Direction::PLUS);
        $ledger->setAmount('1.0000');
        $ledger->setCurrency('USD');
        $ledger->setReason('init');
        $ledger->setCreateTime(new \DateTimeImmutable());

        return $ledger;
    }

    protected function getRepository(): LedgerRepository
    {
        $repository = self::getContainer()->get(LedgerRepository::class);
        self::assertInstanceOf(LedgerRepository::class, $repository);

        return $repository;
    }

    public function testSave(): void
    {
        $ledger = $this->createTestLedger();

        $this->getRepository()->save($ledger, true);

        $found = $this->getRepository()->find('ledger-1');
        $this->assertNotNull($found);
        $this->assertSame('ledger-1', $found->getId());
        $this->assertSame('reward-123', $found->getRewardId());
        $this->assertSame(Direction::PLUS, $found->getDirection());
        $this->assertSame('100.0000', $found->getAmount());
        $this->assertSame('USD', $found->getCurrency());
        $this->assertSame('Referral bonus', $found->getReason());
    }

    public function testSaveWithoutFlush(): void
    {
        $ledger = $this->createTestLedger('ledger-no-flush');

        $this->getRepository()->save($ledger, false);

        // 手动flush以验证数据已持久化
        self::getEntityManager()->flush();

        $found = $this->getRepository()->find('ledger-no-flush');
        $this->assertNotNull($found);
        $this->assertSame('reward-123', $found->getRewardId());
    }

    public function testRemove(): void
    {
        $ledger = $this->createTestLedger('ledger-to-remove');
        $this->getRepository()->save($ledger, true);

        $this->getRepository()->remove($ledger, true);

        $found = $this->getRepository()->find('ledger-to-remove');
        $this->assertNull($found);
    }

    // testRemoveWithoutFlush() 由基类提供

    public function testFindByRewardId(): void
    {
        // 创建多个与同一奖励相关的账本记录
        $ledger1 = $this->createTestLedger('ledger-1');
        $ledger1->setRewardId('reward-match');
        $this->getRepository()->save($ledger1, true);

        $ledger2 = $this->createTestLedger('ledger-2');
        $ledger2->setRewardId('reward-match');
        $ledger2->setDirection(Direction::MINUS);
        $ledger2->setAmount('25.0000');
        $ledger2->setReason('Adjustment');
        $this->getRepository()->save($ledger2, true);

        // 创建不匹配的账本记录
        $ledger3 = $this->createTestLedger('ledger-3');
        $ledger3->setRewardId('reward-different');
        $this->getRepository()->save($ledger3, true);

        // 查找匹配的记录
        $results = $this->getRepository()->findByRewardId('reward-match');
        $this->assertCount(2, $results);

        $ledgerIds = array_map(fn (Ledger $l) => $l->getId(), $results);
        $this->assertContains('ledger-1', $ledgerIds);
        $this->assertContains('ledger-2', $ledgerIds);

        // 查找不匹配的条件
        $results = $this->getRepository()->findByRewardId('reward-nonexistent');
        $this->assertCount(0, $results);
    }

    public function testFindByRewardIdWithDifferentDirections(): void
    {
        $rewardId = 'reward-directions-test';

        // 创建加账记录
        $plusLedger = $this->createTestLedger('ledger-plus');
        $plusLedger->setRewardId($rewardId);
        $plusLedger->setDirection(Direction::PLUS);
        $plusLedger->setAmount('150.0000');
        $this->getRepository()->save($plusLedger, true);

        // 创建减账记录
        $minusLedger = $this->createTestLedger('ledger-minus');
        $minusLedger->setRewardId($rewardId);
        $minusLedger->setDirection(Direction::MINUS);
        $minusLedger->setAmount('50.0000');
        $this->getRepository()->save($minusLedger, true);

        $results = $this->getRepository()->findByRewardId($rewardId);
        $this->assertCount(2, $results);

        // 验证包含不同方向的记录
        $directions = array_map(fn (Ledger $l) => $l->getDirection(), $results);
        $this->assertContains(Direction::PLUS, $directions);
        $this->assertContains(Direction::MINUS, $directions);
    }

    public function testLedgerWithDifferentCurrencies(): void
    {
        // 测试不同货币
        $currencies = ['USD', 'EUR', 'CNY', 'JPY'];

        foreach ($currencies as $index => $currency) {
            $ledger = $this->createTestLedger("ledger-{$currency}");
            $ledger->setCurrency($currency);
            $ledger->setAmount('100.0000');
            $this->getRepository()->save($ledger, true);
        }

        // 验证每个货币的记录都正确保存
        foreach ($currencies as $currency) {
            $found = $this->getRepository()->find("ledger-{$currency}");
            $this->assertNotNull($found);
            $this->assertSame($currency, $found->getCurrency());
        }
    }

    public function testLedgerWithPreciseAmounts(): void
    {
        $amounts = [
            '0.0001',
            '999.9999',
            '12345.6789',
            '999999999999.0000',
        ];

        foreach ($amounts as $index => $amount) {
            $ledger = $this->createTestLedger("ledger-amount-{$index}");
            $ledger->setAmount($amount);
            $this->getRepository()->save($ledger, true);

            $found = $this->getRepository()->find("ledger-amount-{$index}");
            $this->assertNotNull($found);
            $this->assertSame($amount, $found->getAmount());
        }
    }

    public function testLedgerWithNullReason(): void
    {
        $ledger = $this->createTestLedger('ledger-null-reason');
        $ledger->setReason(null);
        $this->getRepository()->save($ledger, true);

        $found = $this->getRepository()->find('ledger-null-reason');
        $this->assertNotNull($found);
        $this->assertNull($found->getReason());
    }

    public function testLedgerWithEmptyReason(): void
    {
        $ledger = $this->createTestLedger('ledger-empty-reason');
        $ledger->setReason('');
        $this->getRepository()->save($ledger, true);

        $found = $this->getRepository()->find('ledger-empty-reason');
        $this->assertNotNull($found);
        $this->assertSame('', $found->getReason());
    }

    public function testLedgerWithLongReason(): void
    {
        $longReason = str_repeat('Long reason text ', 15); // 接近255字符限制
        $ledger = $this->createTestLedger('ledger-long-reason');
        $ledger->setReason($longReason);
        $this->getRepository()->save($ledger, true);

        $found = $this->getRepository()->find('ledger-long-reason');
        $this->assertNotNull($found);
        $this->assertSame($longReason, $found->getReason());
    }

    public function testLedgerTimeStamp(): void
    {
        $testTime = new \DateTimeImmutable('2023-12-01 15:30:45');

        $ledger = $this->createTestLedger('ledger-timestamp');
        $ledger->setCreateTime($testTime);
        $this->getRepository()->save($ledger, true);

        $found = $this->getRepository()->find('ledger-timestamp');
        $this->assertNotNull($found);
        $this->assertEquals($testTime, $found->getCreateTime());
    }

    public function testLedgerStringRepresentation(): void
    {
        // 测试加账的字符串表示
        $plusLedger = $this->createTestLedger('plus-ledger');
        $plusLedger->setDirection(Direction::PLUS);
        $plusLedger->setAmount('100.0000');
        $plusLedger->setCurrency('USD');

        $this->assertSame('+ 100.0000 USD', (string) $plusLedger);

        // 测试减账的字符串表示
        $minusLedger = $this->createTestLedger('minus-ledger');
        $minusLedger->setDirection(Direction::MINUS);
        $minusLedger->setAmount('50.0000');
        $minusLedger->setCurrency('EUR');

        $this->assertSame('- 50.0000 EUR', (string) $minusLedger);
    }

    public function testMultipleLedgerEntriesForSameReward(): void
    {
        $rewardId = 'reward-multiple-entries';
        $expectedTotal = 0;

        // 创建多个账本记录
        $entries = [
            ['amount' => '100.0000', 'direction' => Direction::PLUS, 'reason' => 'Initial reward'],
            ['amount' => '25.0000', 'direction' => Direction::PLUS, 'reason' => 'Bonus'],
            ['amount' => '10.0000', 'direction' => Direction::MINUS, 'reason' => 'Adjustment'],
            ['amount' => '5.0000', 'direction' => Direction::MINUS, 'reason' => 'Fee'],
        ];

        foreach ($entries as $index => $entry) {
            $ledger = $this->createTestLedger("ledger-multi-{$index}");
            $ledger->setRewardId($rewardId);
            $ledger->setAmount($entry['amount']);
            $ledger->setDirection($entry['direction']);
            $ledger->setReason($entry['reason']);
            $this->getRepository()->save($ledger, true);
        }

        $results = $this->getRepository()->findByRewardId($rewardId);
        $this->assertCount(4, $results);

        // 验证每个记录都有正确的属性
        foreach ($results as $ledger) {
            $this->assertSame($rewardId, $ledger->getRewardId());
            $this->assertNotNull($ledger->getReason());
        }
    }

    private function createTestLedger(string $id = 'ledger-1'): Ledger
    {
        $ledger = new Ledger();
        $ledger->setId($id);
        $ledger->setRewardId('reward-123');
        $ledger->setDirection(Direction::PLUS);
        $ledger->setAmount('100.0000');
        $ledger->setCurrency('USD');
        $ledger->setReason('Referral bonus');
        $ledger->setCreateTime(new \DateTimeImmutable());

        return $ledger;
    }
}
