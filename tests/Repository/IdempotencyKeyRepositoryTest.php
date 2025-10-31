<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\MgmCoreBundle\Entity\IdempotencyKey;
use Tourze\MgmCoreBundle\Repository\IdempotencyKeyRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(IdempotencyKeyRepository::class)]
#[RunTestsInSeparateProcesses]
class IdempotencyKeyRepositoryTest extends AbstractRepositoryTestCase
{
    protected function onSetUp(): void
    {
    }

    protected function createNewEntity(): object
    {
        $entity = new IdempotencyKey();
        $entity->setKey('test-key-' . uniqid());
        $entity->setScope('reward-processing');
        $entity->setResultJson(['status' => 'success']);
        $entity->setCreateTime(new \DateTimeImmutable());

        return $entity;
    }

    protected function getRepository(): IdempotencyKeyRepository
    {
        $repository = self::getContainer()->get(IdempotencyKeyRepository::class);
        self::assertInstanceOf(IdempotencyKeyRepository::class, $repository);

        return $repository;
    }

    public function testSave(): void
    {
        $idempotencyKey = $this->createTestIdempotencyKey();

        $this->getRepository()->save($idempotencyKey, true);

        $found = $this->getRepository()->findByKey('test-key-123');
        $this->assertNotNull($found);
        $this->assertSame('test-key-123', $found->getKey());
        $this->assertSame('reward-processing', $found->getScope());
        $this->assertSame(['status' => 'success', 'reward_id' => 'reward-123'], $found->getResultJson());
    }

    public function testSaveWithoutFlush(): void
    {
        $idempotencyKey = $this->createTestIdempotencyKey('key-no-flush');

        $this->getRepository()->save($idempotencyKey, false);

        // 手动flush以验证数据已持久化
        self::getEntityManager()->flush();

        $found = $this->getRepository()->findByKey('key-no-flush');
        $this->assertNotNull($found);
        $this->assertSame('reward-processing', $found->getScope());
    }

    public function testRemove(): void
    {
        $idempotencyKey = $this->createTestIdempotencyKey('key-to-remove');
        $this->getRepository()->save($idempotencyKey, true);

        $this->getRepository()->remove($idempotencyKey, true);

        $found = $this->getRepository()->findByKey('key-to-remove');
        $this->assertNull($found);
    }

    // testRemoveWithoutFlush() 由基类提供

    public function testFindByKey(): void
    {
        // 创建多个不同的幂等性键
        $key1 = $this->createTestIdempotencyKey('unique-key-1');
        $key1->setScope('scope-1');
        $this->getRepository()->save($key1, true);

        $key2 = $this->createTestIdempotencyKey('unique-key-2');
        $key2->setScope('scope-2');
        $this->getRepository()->save($key2, true);

        // 测试查找存在的键
        $result = $this->getRepository()->findByKey('unique-key-1');
        $this->assertNotNull($result);
        $this->assertSame('unique-key-1', $result->getKey());
        $this->assertSame('scope-1', $result->getScope());

        // 测试查找另一个存在的键
        $result = $this->getRepository()->findByKey('unique-key-2');
        $this->assertNotNull($result);
        $this->assertSame('unique-key-2', $result->getKey());
        $this->assertSame('scope-2', $result->getScope());

        // 测试查找不存在的键
        $result = $this->getRepository()->findByKey('non-existent-key');
        $this->assertNull($result);
    }

    public function testIdempotencyKeyWithComplexResultJson(): void
    {
        $complexResult = [
            'operation' => 'reward_grant',
            'timestamp' => '2023-12-01T10:00:00Z',
            'rewards' => [
                ['type' => 'points', 'amount' => 100, 'recipient' => 'user-123'],
                ['type' => 'discount', 'amount' => 15.5, 'recipient' => 'user-456'],
            ],
            'metadata' => [
                'campaign_id' => 'campaign-abc',
                'referral_id' => 'referral-xyz',
            ],
        ];

        $idempotencyKey = $this->createTestIdempotencyKey('complex-result');
        $idempotencyKey->setResultJson($complexResult);
        $this->getRepository()->save($idempotencyKey, true);

        $found = $this->getRepository()->findByKey('complex-result');
        $this->assertNotNull($found);

        $result = $found->getResultJson();
        $this->assertIsArray($result);
        $this->assertSame('reward_grant', $result['operation']);
        $this->assertCount(2, $result['rewards']);
        $this->assertSame(100, $result['rewards'][0]['amount']);
        $this->assertSame('campaign-abc', $result['metadata']['campaign_id']);
    }

    public function testIdempotencyKeyWithEmptyResultJson(): void
    {
        $idempotencyKey = $this->createTestIdempotencyKey('empty-result');
        $idempotencyKey->setResultJson([]);
        $this->getRepository()->save($idempotencyKey, true);

        $found = $this->getRepository()->findByKey('empty-result');
        $this->assertNotNull($found);
        $this->assertSame([], $found->getResultJson());
    }

    public function testIdempotencyKeyWithDifferentScopes(): void
    {
        $scopes = [
            'reward-processing',
            'referral-creation',
            'qualification-check',
            'ledger-update',
        ];

        $keys = [];
        foreach ($scopes as $index => $scope) {
            $key = $this->createTestIdempotencyKey("key-scope-{$index}");
            $key->setScope($scope);
            $this->getRepository()->save($key, true);
            $keys[] = $key;
        }

        // 验证每个scope的键都能正确查找
        foreach ($keys as $index => $savedKey) {
            $found = $this->getRepository()->findByKey("key-scope-{$index}");
            $this->assertNotNull($found);
            $this->assertSame($scopes[$index], $found->getScope());
        }
    }

    public function testIdempotencyKeyTimeStamp(): void
    {
        $testTime = new \DateTimeImmutable('2023-12-01 15:30:45');

        $idempotencyKey = $this->createTestIdempotencyKey('timestamp-test');
        $idempotencyKey->setCreateTime($testTime);
        $this->getRepository()->save($idempotencyKey, true);

        $found = $this->getRepository()->findByKey('timestamp-test');
        $this->assertNotNull($found);
        $this->assertEquals($testTime, $found->getCreateTime());
    }

    public function testMultipleKeysWithSameScope(): void
    {
        // 创建多个具有相同scope但不同key的记录
        $scope = 'same-scope';

        $key1 = $this->createTestIdempotencyKey('key-1-same-scope');
        $key1->setScope($scope);
        $this->getRepository()->save($key1, true);

        $key2 = $this->createTestIdempotencyKey('key-2-same-scope');
        $key2->setScope($scope);
        $this->getRepository()->save($key2, true);

        // 验证每个键都能独立查找
        $found1 = $this->getRepository()->findByKey('key-1-same-scope');
        $this->assertNotNull($found1);
        $this->assertSame($scope, $found1->getScope());

        $found2 = $this->getRepository()->findByKey('key-2-same-scope');
        $this->assertNotNull($found2);
        $this->assertSame($scope, $found2->getScope());

        // 确保是不同的记录
        $this->assertNotSame($found1->getKey(), $found2->getKey());
    }

    public function testIdempotencyKeyStringRepresentation(): void
    {
        $idempotencyKey = $this->createTestIdempotencyKey('string-test');
        $this->getRepository()->save($idempotencyKey, true);

        // 测试__toString方法
        $this->assertSame('string-test', (string) $idempotencyKey);
    }

    public function testFindByKeyAndScope(): void
    {
        $key = new IdempotencyKey();
        $key->setKey('k1');
        $key->setScope('s1');
        $key->setResultJson([]);
        $key->setCreateTime(new \DateTimeImmutable());
        $this->getRepository()->save($key, true);

        $found = $this->getRepository()->findByKeyAndScope('k1', 's1');
        $this->assertNotNull($found);
        $this->assertSame('k1', $found->getKey());
        $this->assertSame('s1', $found->getScope());
    }

    private function createTestIdempotencyKey(string $key = 'test-key-123'): IdempotencyKey
    {
        $idempotencyKey = new IdempotencyKey();
        $idempotencyKey->setKey($key);
        $idempotencyKey->setScope('reward-processing');
        $idempotencyKey->setResultJson(['status' => 'success', 'reward_id' => 'reward-123']);
        $idempotencyKey->setCreateTime(new \DateTimeImmutable());

        return $idempotencyKey;
    }
}
