<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\MgmCoreBundle\Enum\Attribution;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(Attribution::class)]
final class AttributionTest extends AbstractEnumTestCase
{
    public function testToArray(): void
    {
        $this->assertSame(['value' => 'first', 'label' => '首次触达'], Attribution::FIRST->toArray());
    }

    public function testLabelsAndToArray(): void
    {
        $this->assertSame('首次触达', Attribution::FIRST->getLabel());
        $this->assertSame('最后触达', Attribution::LAST->getLabel());

        $item = Attribution::FIRST->toArray();
        $this->assertSame(['value' => 'first', 'label' => '首次触达'], $item);
    }
}
