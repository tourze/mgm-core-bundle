<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\MgmCoreBundle\Exception\ReferralException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(ReferralException::class)]
final class ReferralExceptionTest extends AbstractExceptionTestCase
{
    public function testFactoryMethods(): void
    {
        $ex1 = ReferralException::selfReferralNotAllowed();
        $this->assertInstanceOf(ReferralException::class, $ex1);
        $this->assertSame('Self-referral not allowed', $ex1->getMessage());

        $ex2 = ReferralException::duplicateReferral();
        $this->assertInstanceOf(ReferralException::class, $ex2);
        $this->assertSame('Referral already exists', $ex2->getMessage());
    }
}
