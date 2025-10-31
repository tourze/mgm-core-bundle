<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\MgmCoreBundle\Entity\IdempotencyKey;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(IdempotencyKey::class)]
class IdempotencyKeyTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new IdempotencyKey();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        $now = new \DateTimeImmutable();
        yield 'key_property' => ['key', 'test-key-456'];
        yield 'scope_property' => ['scope', 'reward-creation'];
        yield 'resultJson_property' => ['resultJson', ['status' => 'success']];
        yield 'createTime_property' => ['createTime', $now];
    }
    // extra behavior tests

    public function testToString(): void
    {
        $idempotencyKey = new IdempotencyKey();
        $idempotencyKey->setKey('test-key-456');

        $this->assertSame('test-key-456', (string) $idempotencyKey);
    }
}
