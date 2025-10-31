<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\MgmCoreBundle\DTO\IssueResult;

/**
 * @internal
 */
#[CoversClass(IssueResult::class)]
class IssueResultTest extends TestCase
{
    public function testConstructionWithRequiredParameters(): void
    {
        $status = 'success';

        $result = new IssueResult($status);

        $this->assertSame($status, $result->status);
        $this->assertNull($result->externalIssueId);
        $this->assertNull($result->reason);
    }

    public function testConstructionWithAllParameters(): void
    {
        $status = 'success';
        $externalIssueId = 'issue-12345';
        $reason = 'Successfully processed';

        $result = new IssueResult($status, $externalIssueId, $reason);

        $this->assertSame($status, $result->status);
        $this->assertSame($externalIssueId, $result->externalIssueId);
        $this->assertSame($reason, $result->reason);
    }

    public function testConstructionWithPartialParameters(): void
    {
        $status = 'pending';
        $externalIssueId = 'issue-67890';

        $result = new IssueResult($status, $externalIssueId);

        $this->assertSame($status, $result->status);
        $this->assertSame($externalIssueId, $result->externalIssueId);
        $this->assertNull($result->reason);
    }

    public function testConstructionWithFailureStatus(): void
    {
        $status = 'failed';
        $externalIssueId = null;
        $reason = 'Insufficient funds';

        $result = new IssueResult($status, $externalIssueId, $reason);

        $this->assertSame($status, $result->status);
        $this->assertNull($result->externalIssueId);
        $this->assertSame($reason, $result->reason);
    }

    public function testConstructionWithEmptyStrings(): void
    {
        $status = '';
        $externalIssueId = '';
        $reason = '';

        $result = new IssueResult($status, $externalIssueId, $reason);

        $this->assertSame($status, $result->status);
        $this->assertSame($externalIssueId, $result->externalIssueId);
        $this->assertSame($reason, $result->reason);
    }

    public function testConstructionWithNullOptionalParameters(): void
    {
        $status = 'processing';

        $result = new IssueResult($status, null, null);

        $this->assertSame($status, $result->status);
        $this->assertNull($result->externalIssueId);
        $this->assertNull($result->reason);
    }

    public function testDifferentStatusValues(): void
    {
        $statuses = ['success', 'failed', 'pending', 'cancelled', 'retry', 'timeout'];

        foreach ($statuses as $status) {
            $result = new IssueResult($status);
            $this->assertSame($status, $result->status);
        }
    }

    public function testPropertiesAreReadonly(): void
    {
        $status = 'readonly_test';
        $externalIssueId = 'readonly-issue';
        $reason = 'readonly reason';

        $result = new IssueResult($status, $externalIssueId, $reason);

        // This test verifies that properties are readonly by checking their reflection
        $reflection = new \ReflectionClass($result);

        $statusProperty = $reflection->getProperty('status');
        $externalIssueIdProperty = $reflection->getProperty('externalIssueId');
        $reasonProperty = $reflection->getProperty('reason');

        $this->assertTrue($statusProperty->isReadOnly());
        $this->assertTrue($externalIssueIdProperty->isReadOnly());
        $this->assertTrue($reasonProperty->isReadOnly());
    }

    public function testConstructionWithLongStrings(): void
    {
        $status = str_repeat('a', 1000);
        $externalIssueId = str_repeat('b', 2000);
        $reason = str_repeat('c', 3000);

        $result = new IssueResult($status, $externalIssueId, $reason);

        $this->assertSame($status, $result->status);
        $this->assertSame($externalIssueId, $result->externalIssueId);
        $this->assertSame($reason, $result->reason);
        $this->assertSame(1000, strlen($result->status));
        $this->assertSame(2000, strlen($result->externalIssueId));
        $this->assertSame(3000, strlen($result->reason));
    }
}
