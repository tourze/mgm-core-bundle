<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\MgmCoreBundle\DTO\Subject;

/**
 * @internal
 */
#[CoversClass(Subject::class)]
class SubjectTest extends TestCase
{
    public function testConstruction(): void
    {
        $type = 'user';
        $id = '123';

        $subject = new Subject($type, $id);

        $this->assertSame($type, $subject->type);
        $this->assertSame($id, $subject->id);
    }

    public function testConstructionWithDifferentTypes(): void
    {
        $testCases = [
            ['user', '12345'],
            ['order', 'order-67890'],
            ['product', 'prod_abc123'],
            ['campaign', 'campaign-xyz'],
            ['transaction', 'txn_999888777'],
        ];

        foreach ($testCases as [$type, $id]) {
            /** @var string $type */
            /** @var string $id */
            $subject = new Subject($type, $id);

            $this->assertSame($type, $subject->type);
            $this->assertSame($id, $subject->id);
        }
    }

    public function testConstructionWithNumericId(): void
    {
        $type = 'user';
        $id = '999';

        $subject = new Subject($type, $id);

        $this->assertSame($type, $subject->type);
        $this->assertSame($id, $subject->id);
        $this->assertIsString($subject->id);
    }

    public function testConstructionWithUuidId(): void
    {
        $type = 'account';
        $id = '550e8400-e29b-41d4-a716-446655440000';

        $subject = new Subject($type, $id);

        $this->assertSame($type, $subject->type);
        $this->assertSame($id, $subject->id);
    }

    public function testConstructionWithEmptyStrings(): void
    {
        $type = '';
        $id = '';

        $subject = new Subject($type, $id);

        $this->assertSame($type, $subject->type);
        $this->assertSame($id, $subject->id);
    }

    public function testConstructionWithSpecialCharacters(): void
    {
        $type = 'user-type_v2';
        $id = 'id-with-special-chars_123@domain.com';

        $subject = new Subject($type, $id);

        $this->assertSame($type, $subject->type);
        $this->assertSame($id, $subject->id);
    }

    public function testConstructionWithLongStrings(): void
    {
        $type = str_repeat('a', 100);
        $id = str_repeat('b', 200);

        $subject = new Subject($type, $id);

        $this->assertSame($type, $subject->type);
        $this->assertSame($id, $subject->id);
        $this->assertSame(100, strlen($subject->type));
        $this->assertSame(200, strlen($subject->id));
    }

    public function testConstructionWithUnicodeCharacters(): void
    {
        $type = '用户类型';
        $id = '用户ID_123';

        $subject = new Subject($type, $id);

        $this->assertSame($type, $subject->type);
        $this->assertSame($id, $subject->id);
    }

    public function testConstructionWithSpaces(): void
    {
        $type = 'user type with spaces';
        $id = 'id with spaces';

        $subject = new Subject($type, $id);

        $this->assertSame($type, $subject->type);
        $this->assertSame($id, $subject->id);
    }

    public function testPropertiesAreReadonly(): void
    {
        $type = 'readonly_test';
        $id = 'readonly_id';

        $subject = new Subject($type, $id);

        // This test verifies that properties are readonly by checking their reflection
        $reflection = new \ReflectionClass($subject);

        $typeProperty = $reflection->getProperty('type');
        $idProperty = $reflection->getProperty('id');

        $this->assertTrue($typeProperty->isReadOnly());
        $this->assertTrue($idProperty->isReadOnly());
    }

    public function testConstructionWithBoundaryValues(): void
    {
        // Test with single character strings
        $subject1 = new Subject('a', 'b');
        $this->assertSame('a', $subject1->type);
        $this->assertSame('b', $subject1->id);

        // Test with zero-width space (edge case)
        $type = 'type' . "\u{200B}"; // zero-width space
        $id = 'id' . "\u{200B}";
        $subject2 = new Subject($type, $id);
        $this->assertSame($type, $subject2->type);
        $this->assertSame($id, $subject2->id);
    }

    public function testMultipleInstancesAreIndependent(): void
    {
        $subject1 = new Subject('user', '123');
        $subject2 = new Subject('order', '456');

        $this->assertNotSame($subject1->type, $subject2->type);
        $this->assertNotSame($subject1->id, $subject2->id);
        $this->assertSame('user', $subject1->type);
        $this->assertSame('123', $subject1->id);
        $this->assertSame('order', $subject2->type);
        $this->assertSame('456', $subject2->id);
    }

    public function testEqualityComparison(): void
    {
        $subject1 = new Subject('user', '123');
        $subject2 = new Subject('user', '123');
        $subject3 = new Subject('user', '456');

        // Objects are not the same instance
        $this->assertNotSame($subject1, $subject2);

        // But they have the same properties
        $this->assertSame($subject1->type, $subject2->type);
        $this->assertSame($subject1->id, $subject2->id);

        // Different data
        $this->assertNotSame($subject1->id, $subject3->id);
    }

    public function testCommonSubjectTypes(): void
    {
        $commonTypes = [
            'user',
            'customer',
            'order',
            'transaction',
            'product',
            'campaign',
            'referral',
            'account',
            'session',
            'device',
        ];

        /** @var int $index */
        /** @var string $type */
        foreach ($commonTypes as $index => $type) {
            $id = 'id-' . $index;
            $subject = new Subject($type, $id);

            $this->assertSame($type, $subject->type);
            $this->assertSame($id, $subject->id);
        }
    }
}
