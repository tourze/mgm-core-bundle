<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\MgmCoreBundle\Enum\ReferralState;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(ReferralState::class)]
final class ReferralStateTest extends AbstractEnumTestCase
{
    public function testToArray(): void
    {
        $this->assertSame(['value' => 'created', 'label' => '创建'], ReferralState::CREATED->toArray());
    }

    public function testLabelsAndToArray(): void
    {
        $this->assertSame('创建', ReferralState::CREATED->getLabel());
        $this->assertSame('已归因', ReferralState::ATTRIBUTED->getLabel());
        $this->assertSame('已合格', ReferralState::QUALIFIED->getLabel());
        $this->assertSame('已发放', ReferralState::REWARDED->getLabel());
        $this->assertSame('已撤销', ReferralState::REVOKED->getLabel());

        $item = ReferralState::CREATED->toArray();
        $this->assertSame(['value' => 'created', 'label' => '创建'], $item);
    }
}
