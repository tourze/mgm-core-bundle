<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\MgmCoreBundle\Enum\RewardState;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(RewardState::class)]
final class RewardStateTest extends AbstractEnumTestCase
{
    public function testToArray(): void
    {
        $this->assertSame(['value' => 'pending', 'label' => '待发放'], RewardState::PENDING->toArray());
    }

    public function testLabelsAndToArray(): void
    {
        $this->assertSame('待发放', RewardState::PENDING->getLabel());
        $this->assertSame('已发放', RewardState::GRANTED->getLabel());
        $this->assertSame('已取消', RewardState::CANCELLED->getLabel());

        $item = RewardState::PENDING->toArray();
        $this->assertSame(['value' => 'pending', 'label' => '待发放'], $item);
    }
}
