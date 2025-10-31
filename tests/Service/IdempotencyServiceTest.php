<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\MgmCoreBundle\Entity\IdempotencyKey;
use Tourze\MgmCoreBundle\Repository\IdempotencyKeyRepository;
use Tourze\MgmCoreBundle\Service\ClockInterface;
use Tourze\MgmCoreBundle\Service\IdempotencyService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(IdempotencyService::class)]
#[RunTestsInSeparateProcesses]
class IdempotencyServiceTest extends AbstractIntegrationTestCase
{
    private IdempotencyService $idempotencyService;

    private IdempotencyKeyRepository $repository;

    private ClockInterface $clock;

    protected function onSetUp(): void
    {
        $this->idempotencyService = self::getService(IdempotencyService::class);
        $this->repository = self::getService(IdempotencyKeyRepository::class);
        $this->clock = self::getService(ClockInterface::class);
    }

    public function testGetOrStoreWithNewKey(): void
    {
        $key = 'test-key-' . uniqid();
        $scope = 'test-scope';
        $expectedResult = ['result' => 'test-operation-result'];

        $operationCalled = false;
        $operation = function () use ($expectedResult, &$operationCalled) {
            $operationCalled = true;

            return $expectedResult;
        };

        $result = $this->idempotencyService->getOrStore($key, $scope, $operation);

        $this->assertTrue($operationCalled);
        $this->assertSame($expectedResult, $result);

        // 验证幂等性键被正确保存
        $idempotencyKey = $this->repository->findOneBy(['key' => $key]);
        $this->assertInstanceOf(IdempotencyKey::class, $idempotencyKey);
        $this->assertSame($key, $idempotencyKey->getKey());
        $this->assertSame($scope, $idempotencyKey->getScope());
        $this->assertSame($expectedResult, $idempotencyKey->getResultJson());
        $this->assertNotNull($idempotencyKey->getCreateTime());
    }

    public function testGetOrStoreWithExistingKey(): void
    {
        $key = 'existing-key-' . uniqid();
        $scope = 'test-scope';
        $originalResult = ['result' => 'original-operation'];

        // 第一次调用，执行操作并保存
        $firstOperationCalled = false;
        $firstOperation = function () use ($originalResult, &$firstOperationCalled) {
            $firstOperationCalled = true;

            return $originalResult;
        };

        $firstResult = $this->idempotencyService->getOrStore($key, $scope, $firstOperation);

        $this->assertTrue($firstOperationCalled);
        $this->assertSame($originalResult, $firstResult);

        // 第二次调用，应该返回缓存结果，不执行操作
        $secondOperationCalled = false;
        $secondOperation = function () use (&$secondOperationCalled) {
            $secondOperationCalled = true;

            return ['result' => 'this-should-not-be-returned'];
        };

        $secondResult = $this->idempotencyService->getOrStore($key, $scope, $secondOperation);

        $this->assertFalse($secondOperationCalled);
        $this->assertSame($originalResult, $secondResult);

        // 验证数据库中只有一条记录
        $idempotencyKeys = $this->repository->findBy(['key' => $key]);
        $this->assertCount(1, $idempotencyKeys);
    }

    public function testGetOrStoreWithDifferentScopes(): void
    {
        $key = 'same-key-' . uniqid();
        $scope1 = 'scope-1';
        $scope2 = 'scope-2';
        $result1 = ['result' => 'result-1'];
        $result2 = ['result' => 'result-2'];

        $operation1Called = false;
        $operation1 = function () use ($result1, &$operation1Called) {
            $operation1Called = true;

            return $result1;
        };

        $operation2Called = false;
        $operation2 = function () use ($result2, &$operation2Called) {
            $operation2Called = true;

            return $result2;
        };

        // 使用相同key但不同scope，应该都执行
        $actualResult1 = $this->idempotencyService->getOrStore($key, $scope1, $operation1);
        $actualResult2 = $this->idempotencyService->getOrStore($key, $scope2, $operation2);

        $this->assertTrue($operation1Called);
        $this->assertTrue($operation2Called);
        $this->assertSame($result1, $actualResult1);
        $this->assertSame($result2, $actualResult2);

        // 验证数据库中有两条记录
        $idempotencyKeys = $this->repository->findBy(['key' => $key]);
        $this->assertCount(2, $idempotencyKeys);

        // 验证scope正确
        $scopes = array_map(fn ($item) => $item->getScope(), $idempotencyKeys);
        $this->assertContains($scope1, $scopes);
        $this->assertContains($scope2, $scopes);
    }

    public function testGetOrStoreWithScalarResult(): void
    {
        $key = 'scalar-key-' . uniqid();
        $scope = 'test-scope';
        $scalarResult = 'simple-string-result';

        $operation = function () use ($scalarResult) {
            return $scalarResult;
        };

        $result = $this->idempotencyService->getOrStore($key, $scope, $operation);

        $this->assertSame($scalarResult, $result);

        // 验证标量结果被正确包装
        $idempotencyKey = $this->repository->findOneBy(['key' => $key]);
        $this->assertNotNull($idempotencyKey);
        $this->assertSame(['result' => $scalarResult], $idempotencyKey->getResultJson());
    }

    public function testGetOrStoreWithArrayResult(): void
    {
        $key = 'array-key-' . uniqid();
        $scope = 'test-scope';
        $arrayResult = ['data' => ['id' => 123, 'name' => 'test'], 'status' => 'success'];

        $operation = function () use ($arrayResult) {
            return $arrayResult;
        };

        $result = $this->idempotencyService->getOrStore($key, $scope, $operation);

        $this->assertSame($arrayResult, $result);

        // 验证数组结果直接保存
        $idempotencyKey = $this->repository->findOneBy(['key' => $key]);
        $this->assertNotNull($idempotencyKey);
        $this->assertSame($arrayResult, $idempotencyKey->getResultJson());
    }

    public function testGetOrStoreWithNullResult(): void
    {
        $key = 'null-key-' . uniqid();
        $scope = 'test-scope';

        $operation = function () {
            return null;
        };

        $result = $this->idempotencyService->getOrStore($key, $scope, $operation);

        $this->assertNull($result);

        // 验证null结果被正确包装
        $idempotencyKey = $this->repository->findOneBy(['key' => $key]);
        $this->assertNotNull($idempotencyKey);
        $this->assertSame(['result' => null], $idempotencyKey->getResultJson());
    }

    public function testGetOrStoreWithBooleanResult(): void
    {
        $key = 'bool-key-' . uniqid();
        $scope = 'test-scope';

        $operation = function () {
            return true;
        };

        $result = $this->idempotencyService->getOrStore($key, $scope, $operation);

        $this->assertTrue($result);

        // 验证布尔结果被正确包装
        $idempotencyKey = $this->repository->findOneBy(['key' => $key]);
        $this->assertNotNull($idempotencyKey);
        $this->assertSame(['result' => true], $idempotencyKey->getResultJson());
    }

    public function testGetOrStoreWithNumericResult(): void
    {
        $key = 'numeric-key-' . uniqid();
        $scope = 'test-scope';
        $numericResult = 42;

        $operation = function () use ($numericResult) {
            return $numericResult;
        };

        $result = $this->idempotencyService->getOrStore($key, $scope, $operation);

        $this->assertSame($numericResult, $result);

        // 验证数字结果被正确包装
        $idempotencyKey = $this->repository->findOneBy(['key' => $key]);
        $this->assertNotNull($idempotencyKey);
        $this->assertSame(['result' => $numericResult], $idempotencyKey->getResultJson());
    }

    public function testGetOrStoreTimestampAccuracy(): void
    {
        $key = 'timestamp-key-' . uniqid();
        $scope = 'test-scope';

        $beforeOperation = $this->clock->now();

        $operation = function () {
            return 'test-result';
        };

        $this->idempotencyService->getOrStore($key, $scope, $operation);

        $afterOperation = $this->clock->now();

        $idempotencyKey = $this->repository->findOneBy(['key' => $key]);
        $this->assertNotNull($idempotencyKey);
        $createTime = $idempotencyKey->getCreateTime();

        // 验证创建时间在合理范围内
        $this->assertGreaterThanOrEqual($beforeOperation->getTimestamp(), $createTime->getTimestamp());
        $this->assertLessThanOrEqual($afterOperation->getTimestamp(), $createTime->getTimestamp());
    }

    public function testGetOrStorePersistence(): void
    {
        $key = 'persistence-key-' . uniqid();
        $scope = 'test-scope';
        $expectedResult = ['persisted' => 'data'];

        $operation = function () use ($expectedResult) {
            return $expectedResult;
        };

        $this->idempotencyService->getOrStore($key, $scope, $operation);

        // 清除实体管理器缓存
        self::getEntityManager()->clear();

        // 再次调用，应该从数据库获取结果
        $operationCalled = false;
        $secondOperation = function () use (&$operationCalled) {
            $operationCalled = true;

            return ['this' => 'should-not-be-called'];
        };

        $result = $this->idempotencyService->getOrStore($key, $scope, $secondOperation);

        $this->assertFalse($operationCalled);
        $this->assertSame($expectedResult, $result);
    }

    public function testGetOrStoreWithComplexOperation(): void
    {
        $key = 'complex-key-' . uniqid();
        $scope = 'complex-scope';

        $complexResult = [
            'id' => 'generated-id-123',
            'timestamp' => time(),
            'nested' => [
                'data' => ['a', 'b', 'c'],
                'metadata' => ['version' => '1.0', 'author' => 'test'],
            ],
        ];

        $operation = function () use ($complexResult) {
            // 模拟复杂操作
            usleep(1000); // 1ms delay

            return $complexResult;
        };

        $result = $this->idempotencyService->getOrStore($key, $scope, $operation);

        $this->assertSame($complexResult, $result);

        // 验证复杂结果正确保存和获取
        $idempotencyKey = $this->repository->findOneBy(['key' => $key]);
        $this->assertNotNull($idempotencyKey);
        $this->assertSame($complexResult, $idempotencyKey->getResultJson());
    }

    public function testGetOrStoreIdempotencyAcrossMultipleCalls(): void
    {
        $key = 'idempotent-key-' . uniqid();
        $scope = 'test-scope';
        $originalResult = ['counter' => 1];

        $callCount = 0;
        $operation = function () use (&$callCount, $originalResult) {
            ++$callCount;

            return array_merge($originalResult, ['call_count' => $callCount]);
        };

        // 多次调用相同的key和scope
        $result1 = $this->idempotencyService->getOrStore($key, $scope, $operation);
        $result2 = $this->idempotencyService->getOrStore($key, $scope, $operation);
        $result3 = $this->idempotencyService->getOrStore($key, $scope, $operation);

        // 操作只应该被调用一次
        $this->assertSame(1, $callCount);

        // 所有结果应该相同
        $this->assertSame($result1, $result2);
        $this->assertSame($result1, $result3);
        $this->assertSame(['counter' => 1, 'call_count' => 1], $result1);
    }
}
