<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\MgmCoreBundle\Enum\Decision;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(Decision::class)]
final class DecisionTest extends AbstractEnumTestCase
{
    public function testToArray(): void
    {
        $this->assertSame(['value' => 'qualified', 'label' => '合格'], Decision::QUALIFIED->toArray());
    }

    public function testLabelsAndToArray(): void
    {
        $this->assertSame('合格', Decision::QUALIFIED->getLabel());
        $this->assertSame('拒绝', Decision::REJECTED->getLabel());

        $item = Decision::QUALIFIED->toArray();
        $this->assertSame(['value' => 'qualified', 'label' => '合格'], $item);
    }
}
