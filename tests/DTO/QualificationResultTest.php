<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\MgmCoreBundle\DTO\QualificationResult;

/**
 * @internal
 */
#[CoversClass(QualificationResult::class)]
class QualificationResultTest extends TestCase
{
    public function testConstructionWithRequiredParameters(): void
    {
        $status = 'qualified';

        $result = new QualificationResult($status);

        $this->assertSame($status, $result->status);
        $this->assertNull($result->reason);
        $this->assertNull($result->referralId);
    }

    public function testConstructionWithAllParameters(): void
    {
        $status = 'qualified';
        $reason = 'Meets all criteria';
        $referralId = 'ref-12345';

        $result = new QualificationResult($status, $reason, $referralId);

        $this->assertSame($status, $result->status);
        $this->assertSame($reason, $result->reason);
        $this->assertSame($referralId, $result->referralId);
    }

    public function testConstructionWithReasonOnly(): void
    {
        $status = 'disqualified';
        $reason = 'Insufficient activity';

        $result = new QualificationResult($status, $reason);

        $this->assertSame($status, $result->status);
        $this->assertSame($reason, $result->reason);
        $this->assertNull($result->referralId);
    }

    public function testConstructionWithReferralIdOnly(): void
    {
        $status = 'pending';
        $reason = null;
        $referralId = 'ref-67890';

        $result = new QualificationResult($status, $reason, $referralId);

        $this->assertSame($status, $result->status);
        $this->assertNull($result->reason);
        $this->assertSame($referralId, $result->referralId);
    }

    public function testConstructionWithEmptyStrings(): void
    {
        $status = '';
        $reason = '';
        $referralId = '';

        $result = new QualificationResult($status, $reason, $referralId);

        $this->assertSame($status, $result->status);
        $this->assertSame($reason, $result->reason);
        $this->assertSame($referralId, $result->referralId);
    }

    public function testConstructionWithNullOptionalParameters(): void
    {
        $status = 'under_review';

        $result = new QualificationResult($status, null, null);

        $this->assertSame($status, $result->status);
        $this->assertNull($result->reason);
        $this->assertNull($result->referralId);
    }

    public function testDifferentStatusValues(): void
    {
        $statuses = [
            'qualified',
            'disqualified',
            'pending',
            'under_review',
            'expired',
            'cancelled',
        ];

        foreach ($statuses as $status) {
            $result = new QualificationResult($status);
            $this->assertSame($status, $result->status);
        }
    }

    public function testQualifiedWithReferralId(): void
    {
        $status = 'qualified';
        $reason = 'First time customer';
        $referralId = 'ref-first-customer-001';

        $result = new QualificationResult($status, $reason, $referralId);

        $this->assertSame($status, $result->status);
        $this->assertSame($reason, $result->reason);
        $this->assertSame($referralId, $result->referralId);
    }

    public function testDisqualifiedWithReason(): void
    {
        $status = 'disqualified';
        $reason = 'Account created less than 30 days ago';

        $result = new QualificationResult($status, $reason);

        $this->assertSame($status, $result->status);
        $this->assertSame($reason, $result->reason);
        $this->assertNull($result->referralId);
    }

    public function testPropertiesAreReadonly(): void
    {
        $status = 'readonly_test';
        $reason = 'readonly reason';
        $referralId = 'readonly-ref';

        $result = new QualificationResult($status, $reason, $referralId);

        // This test verifies that properties are readonly by checking their reflection
        $reflection = new \ReflectionClass($result);

        $statusProperty = $reflection->getProperty('status');
        $reasonProperty = $reflection->getProperty('reason');
        $referralIdProperty = $reflection->getProperty('referralId');

        $this->assertTrue($statusProperty->isReadOnly());
        $this->assertTrue($reasonProperty->isReadOnly());
        $this->assertTrue($referralIdProperty->isReadOnly());
    }

    public function testConstructionWithLongStrings(): void
    {
        $status = str_repeat('a', 500);
        $reason = str_repeat('b', 1000);
        $referralId = str_repeat('c', 200);

        $result = new QualificationResult($status, $reason, $referralId);

        $this->assertSame($status, $result->status);
        $this->assertSame($reason, $result->reason);
        $this->assertSame($referralId, $result->referralId);
        $this->assertSame(500, strlen($result->status));
        $this->assertSame(1000, strlen($result->reason));
        $this->assertSame(200, strlen($result->referralId));
    }

    public function testConstructionWithSpecialCharacters(): void
    {
        $status = 'qualified-✓';
        $reason = 'User has completed verification: ✓✓✓';
        $referralId = 'ref-特殊字符-123';

        $result = new QualificationResult($status, $reason, $referralId);

        $this->assertSame($status, $result->status);
        $this->assertSame($reason, $result->reason);
        $this->assertSame($referralId, $result->referralId);
    }
}
