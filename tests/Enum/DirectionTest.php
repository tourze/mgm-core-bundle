<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\MgmCoreBundle\Enum\Direction;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(Direction::class)]
final class DirectionTest extends AbstractEnumTestCase
{
    public function testToArray(): void
    {
        $this->assertSame(['value' => '+', 'label' => '加'], Direction::PLUS->toArray());
    }

    public function testLabelsAndToArray(): void
    {
        $this->assertSame('加', Direction::PLUS->getLabel());
        $this->assertSame('减', Direction::MINUS->getLabel());

        $item = Direction::PLUS->toArray();
        $this->assertSame(['value' => '+', 'label' => '加'], $item);
    }
}
