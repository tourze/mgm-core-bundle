<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\MgmCoreBundle\DTO\RewardResult;

/**
 * @internal
 */
#[CoversClass(RewardResult::class)]
class RewardResultTest extends TestCase
{
    public function testConstructionWithRequiredParameters(): void
    {
        $status = 'issued';

        $result = new RewardResult($status);

        $this->assertSame($status, $result->status);
        $this->assertNull($result->rewardId);
        $this->assertNull($result->reason);
    }

    public function testConstructionWithAllParameters(): void
    {
        $status = 'issued';
        $rewardId = 'reward-12345';
        $reason = 'Successfully issued reward';

        $result = new RewardResult($status, $rewardId, $reason);

        $this->assertSame($status, $result->status);
        $this->assertSame($rewardId, $result->rewardId);
        $this->assertSame($reason, $result->reason);
    }

    public function testConstructionWithRewardIdOnly(): void
    {
        $status = 'issued';
        $rewardId = 'reward-67890';

        $result = new RewardResult($status, $rewardId);

        $this->assertSame($status, $result->status);
        $this->assertSame($rewardId, $result->rewardId);
        $this->assertNull($result->reason);
    }

    public function testConstructionWithReasonOnly(): void
    {
        $status = 'failed';
        $rewardId = null;
        $reason = 'Insufficient balance';

        $result = new RewardResult($status, $rewardId, $reason);

        $this->assertSame($status, $result->status);
        $this->assertNull($result->rewardId);
        $this->assertSame($reason, $result->reason);
    }

    public function testConstructionWithEmptyStrings(): void
    {
        $status = '';
        $rewardId = '';
        $reason = '';

        $result = new RewardResult($status, $rewardId, $reason);

        $this->assertSame($status, $result->status);
        $this->assertSame($rewardId, $result->rewardId);
        $this->assertSame($reason, $result->reason);
    }

    public function testConstructionWithNullOptionalParameters(): void
    {
        $status = 'pending';

        $result = new RewardResult($status, null, null);

        $this->assertSame($status, $result->status);
        $this->assertNull($result->rewardId);
        $this->assertNull($result->reason);
    }

    public function testDifferentStatusValues(): void
    {
        $statuses = [
            'issued',
            'failed',
            'pending',
            'cancelled',
            'expired',
            'processing',
            'rejected',
        ];

        foreach ($statuses as $status) {
            $result = new RewardResult($status);
            $this->assertSame($status, $result->status);
        }
    }

    public function testIssuedWithRewardId(): void
    {
        $status = 'issued';
        $rewardId = 'reward-success-001';
        $reason = 'Reward successfully processed and issued';

        $result = new RewardResult($status, $rewardId, $reason);

        $this->assertSame($status, $result->status);
        $this->assertSame($rewardId, $result->rewardId);
        $this->assertSame($reason, $result->reason);
    }

    public function testFailedWithReason(): void
    {
        $status = 'failed';
        $reason = 'User account is suspended';

        $result = new RewardResult($status, null, $reason);

        $this->assertSame($status, $result->status);
        $this->assertNull($result->rewardId);
        $this->assertSame($reason, $result->reason);
    }

    public function testPendingWithPartialData(): void
    {
        $status = 'pending';
        $rewardId = 'reward-pending-123';

        $result = new RewardResult($status, $rewardId);

        $this->assertSame($status, $result->status);
        $this->assertSame($rewardId, $result->rewardId);
        $this->assertNull($result->reason);
    }

    public function testPropertiesAreReadonly(): void
    {
        $status = 'readonly_test';
        $rewardId = 'readonly-reward';
        $reason = 'readonly reason';

        $result = new RewardResult($status, $rewardId, $reason);

        // This test verifies that properties are readonly by checking their reflection
        $reflection = new \ReflectionClass($result);

        $statusProperty = $reflection->getProperty('status');
        $rewardIdProperty = $reflection->getProperty('rewardId');
        $reasonProperty = $reflection->getProperty('reason');

        $this->assertTrue($statusProperty->isReadOnly());
        $this->assertTrue($rewardIdProperty->isReadOnly());
        $this->assertTrue($reasonProperty->isReadOnly());
    }

    public function testConstructionWithLongStrings(): void
    {
        $status = str_repeat('a', 500);
        $rewardId = str_repeat('b', 1000);
        $reason = str_repeat('c', 2000);

        $result = new RewardResult($status, $rewardId, $reason);

        $this->assertSame($status, $result->status);
        $this->assertSame($rewardId, $result->rewardId);
        $this->assertSame($reason, $result->reason);
        $this->assertSame(500, strlen($result->status));
        $this->assertSame(1000, strlen($result->rewardId));
        $this->assertSame(2000, strlen($result->reason));
    }

    public function testConstructionWithSpecialCharacters(): void
    {
        $status = 'issued-✓';
        $rewardId = 'reward-特殊字符-123';
        $reason = 'Successfully processed: ✓✓✓ with émojis and ñ';

        $result = new RewardResult($status, $rewardId, $reason);

        $this->assertSame($status, $result->status);
        $this->assertSame($rewardId, $result->rewardId);
        $this->assertSame($reason, $result->reason);
    }

    public function testCancelledStatus(): void
    {
        $status = 'cancelled';
        $rewardId = 'reward-cancelled-456';
        $reason = 'User requested cancellation before processing';

        $result = new RewardResult($status, $rewardId, $reason);

        $this->assertSame($status, $result->status);
        $this->assertSame($rewardId, $result->rewardId);
        $this->assertSame($reason, $result->reason);
    }

    public function testRejectedStatus(): void
    {
        $status = 'rejected';
        $reason = 'Does not meet reward criteria';

        $result = new RewardResult($status, null, $reason);

        $this->assertSame($status, $result->status);
        $this->assertNull($result->rewardId);
        $this->assertSame($reason, $result->reason);
    }

    public function testProcessingStatus(): void
    {
        $status = 'processing';
        $rewardId = 'reward-proc-789';
        $reason = 'Reward is being processed by payment provider';

        $result = new RewardResult($status, $rewardId, $reason);

        $this->assertSame($status, $result->status);
        $this->assertSame($rewardId, $result->rewardId);
        $this->assertSame($reason, $result->reason);
    }
}
