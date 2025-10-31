<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\MgmCoreBundle\Enum\Beneficiary;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(Beneficiary::class)]
final class BeneficiaryTest extends AbstractEnumTestCase
{
    public function testToArray(): void
    {
        $this->assertSame(['value' => 'referrer', 'label' => '推荐人'], Beneficiary::REFERRER->toArray());
    }

    public function testLabelsAndToArray(): void
    {
        $this->assertSame('推荐人', Beneficiary::REFERRER->getLabel());
        $this->assertSame('被推荐人', Beneficiary::REFEREE->getLabel());

        $item = Beneficiary::REFERRER->toArray();
        $this->assertSame(['value' => 'referrer', 'label' => '推荐人'], $item);
    }
}
