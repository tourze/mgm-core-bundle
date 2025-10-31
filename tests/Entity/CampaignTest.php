<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\MgmCoreBundle\Entity\Campaign;
use Tourze\MgmCoreBundle\Enum\Attribution;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(Campaign::class)]
class CampaignTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new Campaign();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        $now = new \DateTimeImmutable();
        yield 'id_property' => ['id', 'campaign-123'];
        yield 'name_property' => ['name', 'Test Campaign'];
        yield 'active_property' => ['active', true];
        yield 'configJson_property' => ['configJson', ['key' => 'value']];
        yield 'windowDays_property' => ['windowDays', 30];
        yield 'attribution_property' => ['attribution', Attribution::FIRST];
        yield 'selfBlock_property' => ['selfBlock', false];
        yield 'budgetLimit_property' => ['budgetLimit', '1000.5000'];
        yield 'createTime_property' => ['createTime', $now];
        yield 'updateTime_property' => ['updateTime', $now];
    }
    // getters & setters covered by AbstractEntityTestCase

    public function testToString(): void
    {
        $campaign = new Campaign();
        $campaign->setName('Spring Promo');

        $this->assertSame('Spring Promo', (string) $campaign);
    }

    public function testActiveBoolean(): void
    {
        $campaign = new Campaign();
        $campaign->setActive(false);
        $this->assertFalse($campaign->isActive());
        $campaign->setActive(true);
        $this->assertTrue($campaign->isActive());
    }

    public function testSelfBlockBoolean(): void
    {
        $campaign = new Campaign();
        $campaign->setSelfBlock(false);
        $this->assertFalse($campaign->isSelfBlock());
        $campaign->setSelfBlock(true);
        $this->assertTrue($campaign->isSelfBlock());
    }
}
