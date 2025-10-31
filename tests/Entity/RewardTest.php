<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\MgmCoreBundle\Entity\Reward;
use Tourze\MgmCoreBundle\Enum\Beneficiary;
use Tourze\MgmCoreBundle\Enum\RewardState;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(Reward::class)]
class RewardTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new Reward();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        $now = new \DateTimeImmutable();
        $grantTime = $now->add(new \DateInterval('PT1H'));
        $revokeTime = $now->add(new \DateInterval('PT2H'));
        yield 'id_property' => ['id', 'reward-123'];
        yield 'referralId_property' => ['referralId', 'referral-456'];
        yield 'beneficiary_property' => ['beneficiary', Beneficiary::REFERRER];
        yield 'beneficiaryType_property' => ['beneficiaryType', 'user'];
        yield 'beneficiaryId_property' => ['beneficiaryId', 'user-123'];
        yield 'type_property' => ['type', 'cash'];
        yield 'specJson_property' => ['specJson', ['amount' => '100.00']];
        yield 'state_property' => ['state', RewardState::GRANTED];
        yield 'externalIssueId_property' => ['externalIssueId', 'external-789'];
        yield 'idemKey_property' => ['idemKey', 'idem-key-012'];
        yield 'createTime_property' => ['createTime', $now];
        yield 'grantTime_property' => ['grantTime', $grantTime];
        yield 'revokeTime_property' => ['revokeTime', $revokeTime];
    }
    // extra behavior tests

    public function testToString(): void
    {
        $reward = new Reward();
        $reward->setType('coupon');
        $this->assertSame('coupon', (string) $reward);
    }
}
