<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\MgmCoreBundle\Entity\Referral;
use Tourze\MgmCoreBundle\Enum\ReferralState;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(Referral::class)]
class ReferralTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new Referral();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        $now = new \DateTimeImmutable();
        $qualifyTime = $now->add(new \DateInterval('PT1H'));
        $rewardTime = $now->add(new \DateInterval('PT2H'));
        yield 'id_property' => ['id', 'referral-123'];
        yield 'campaignId_property' => ['campaignId', 'campaign-456'];
        yield 'referrerType_property' => ['referrerType', 'user'];
        yield 'referrerId_property' => ['referrerId', 'user-789'];
        yield 'refereeType_property' => ['refereeType', 'user'];
        yield 'refereeId_property' => ['refereeId', 'user-012'];
        yield 'token_property' => ['token', 'token-345'];
        yield 'source_property' => ['source', 'web'];
        yield 'state_property' => ['state', ReferralState::QUALIFIED];
        yield 'createTime_property' => ['createTime', $now];
        yield 'qualifyTime_property' => ['qualifyTime', $qualifyTime];
        yield 'rewardTime_property' => ['rewardTime', $rewardTime];
    }
    // extra behavior tests

    public function testToString(): void
    {
        $referral = new Referral();
        $referral->setCampaignId('campaign-456');
        $this->assertSame('campaign-456', (string) $referral);
    }
}
