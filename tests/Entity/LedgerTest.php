<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\MgmCoreBundle\Entity\Ledger;
use Tourze\MgmCoreBundle\Enum\Direction;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(Ledger::class)]
class LedgerTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new Ledger();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        $now = new \DateTimeImmutable();
        yield 'id_property' => ['id', 'ledger-123'];
        yield 'rewardId_property' => ['rewardId', 'reward-456'];
        yield 'direction_property' => ['direction', Direction::PLUS];
        yield 'amount_property' => ['amount', '100.0000'];
        yield 'currency_property' => ['currency', 'USD'];
        yield 'reason_property' => ['reason', 'Initial reward'];
        yield 'createTime_property' => ['createTime', $now];
    }
    // extra behavior tests

    public function testToString(): void
    {
        $ledger = new Ledger();
        $ledger->setDirection(Direction::PLUS);
        $ledger->setAmount('50.5000');
        $ledger->setCurrency('EUR');

        $this->assertSame('+ 50.5000 EUR', (string) $ledger);

        $ledger->setDirection(Direction::MINUS);
        $ledger->setAmount('25.0000');
        $ledger->setCurrency('USD');

        $this->assertSame('- 25.0000 USD', (string) $ledger);
    }

    public function testDirectionEnum(): void
    {
        $ledger = new Ledger();
        $ledger->setDirection(Direction::PLUS);
        $this->assertSame(Direction::PLUS, $ledger->getDirection());
        $ledger->setDirection(Direction::MINUS);
        $this->assertSame(Direction::MINUS, $ledger->getDirection());
    }
}
