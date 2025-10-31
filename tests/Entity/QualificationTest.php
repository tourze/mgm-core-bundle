<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\MgmCoreBundle\Entity\Qualification;
use Tourze\MgmCoreBundle\Enum\Decision;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(Qualification::class)]
class QualificationTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new Qualification();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        $now = new \DateTimeImmutable();
        $occurTime = $now->sub(new \DateInterval('PT1H'));
        yield 'id_property' => ['id', 'qual-123'];
        yield 'referralId_property' => ['referralId', 'referral-456'];
        yield 'decision_property' => ['decision', Decision::QUALIFIED];
        yield 'reason_property' => ['reason', 'User completed first order'];
        yield 'evidenceJson_property' => ['evidenceJson', ['order_id' => 'order-123']];
        yield 'occurTime_property' => ['occurTime', $occurTime];
        yield 'createTime_property' => ['createTime', $now];
    }
    // extra behavior tests

    public function testToString(): void
    {
        $qualification = new Qualification();
        $qualification->setReferralId('referral-456');
        $qualification->setEvidenceJson(['order_id' => 'order-123']);
        $qualification->setDecision(Decision::REJECTED);
        $qualification->setReason('Order cancelled');

        $this->assertSame('referral-456 - rejected', (string) $qualification);
    }
}
