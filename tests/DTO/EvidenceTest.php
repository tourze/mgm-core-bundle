<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\DTO;

use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\MgmCoreBundle\DTO\Evidence;

/**
 * @internal
 */
#[CoversClass(Evidence::class)]
class EvidenceTest extends TestCase
{
    public function testConstructionWithRequiredParameters(): void
    {
        $type = 'purchase';
        $id = 'evidence-123';
        $occurTime = new \DateTimeImmutable('2023-10-15 14:30:00');

        $evidence = new Evidence($type, $id, $occurTime);

        $this->assertSame($type, $evidence->type);
        $this->assertSame($id, $evidence->id);
        $this->assertSame($occurTime, $evidence->occurTime);
        $this->assertSame([], $evidence->attrs);
    }

    public function testConstructionWithAllParameters(): void
    {
        $type = 'purchase';
        $id = 'evidence-123';
        $occurTime = new \DateTimeImmutable('2023-10-15 14:30:00');
        $attrs = [
            'amount' => '100.00',
            'currency' => 'USD',
            'product_id' => 'product-456',
            'nested' => [
                'key' => 'value',
                'count' => 42,
            ],
        ];

        $evidence = new Evidence($type, $id, $occurTime, $attrs);

        $this->assertSame($type, $evidence->type);
        $this->assertSame($id, $evidence->id);
        $this->assertSame($occurTime, $evidence->occurTime);
        $this->assertSame($attrs, $evidence->attrs);
    }

    public function testConstructionWithEmptyStrings(): void
    {
        $type = '';
        $id = '';
        $occurTime = new \DateTimeImmutable('2023-10-15 14:30:00');

        $evidence = new Evidence($type, $id, $occurTime);

        $this->assertSame($type, $evidence->type);
        $this->assertSame($id, $evidence->id);
        $this->assertSame($occurTime, $evidence->occurTime);
        $this->assertSame([], $evidence->attrs);
    }

    public function testConstructionWithDifferentDateTimeImplementation(): void
    {
        $type = 'registration';
        $id = 'evidence-789';
        $occurTime = new \DateTimeImmutable('2023-10-15 14:30:00');

        $evidence = new Evidence($type, $id, $occurTime);

        $this->assertSame($type, $evidence->type);
        $this->assertSame($id, $evidence->id);
        $this->assertSame($occurTime, $evidence->occurTime);
        $this->assertInstanceOf(\DateTimeInterface::class, $evidence->occurTime);
    }

    public function testConstructionWithComplexAttrsArray(): void
    {
        $type = 'complex_event';
        $id = 'evidence-complex';
        $occurTime = new \DateTimeImmutable('2023-10-15 14:30:00');
        $attrs = [
            'string_value' => 'test',
            'int_value' => 123,
            'float_value' => 45.67,
            'bool_value' => true,
            'null_value' => null,
            'array_value' => [1, 2, 3],
            'nested_object' => [
                'name' => 'John',
                'age' => 30,
                'active' => false,
            ],
        ];

        $evidence = new Evidence($type, $id, $occurTime, $attrs);

        $this->assertSame($attrs, $evidence->attrs);
        $this->assertSame('test', $evidence->attrs['string_value']);
        $this->assertSame(123, $evidence->attrs['int_value']);
        $this->assertSame(45.67, $evidence->attrs['float_value']);
        $this->assertTrue($evidence->attrs['bool_value']);
        $this->assertArrayHasKey('null_value', $evidence->attrs);
        $this->assertSame(null, $evidence->attrs['null_value']);
        $this->assertSame([1, 2, 3], $evidence->attrs['array_value']);
        $this->assertSame('John', $evidence->attrs['nested_object']['name']);
    }

    public function testPropertiesAreReadonly(): void
    {
        $type = 'readonly_test';
        $id = 'evidence-readonly';
        $occurTime = new \DateTimeImmutable('2023-10-15 14:30:00');
        $attrs = ['test' => 'value'];

        $evidence = new Evidence($type, $id, $occurTime, $attrs);

        // This test verifies that properties are readonly by checking their reflection
        $reflection = new \ReflectionClass($evidence);

        $typeProperty = $reflection->getProperty('type');
        $idProperty = $reflection->getProperty('id');
        $occurTimeProperty = $reflection->getProperty('occurTime');
        $attrsProperty = $reflection->getProperty('attrs');

        $this->assertTrue($typeProperty->isReadOnly());
        $this->assertTrue($idProperty->isReadOnly());
        $this->assertTrue($occurTimeProperty->isReadOnly());
        $this->assertTrue($attrsProperty->isReadOnly());
    }
}
