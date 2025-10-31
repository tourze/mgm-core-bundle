<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\MgmCoreBundle\Exception\CampaignException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(CampaignException::class)]
final class CampaignExceptionTest extends AbstractExceptionTestCase
{
    public function testFactoryMethods(): void
    {
        $ex1 = CampaignException::campaignNotFound('abc');
        $this->assertInstanceOf(CampaignException::class, $ex1);
        $this->assertSame('Campaign not found: abc', $ex1->getMessage());

        $ex2 = CampaignException::campaignInactive('xyz');
        $this->assertInstanceOf(CampaignException::class, $ex2);
        $this->assertSame('Campaign is inactive: xyz', $ex2->getMessage());
    }
}
