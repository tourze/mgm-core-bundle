<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Uid\Ulid;
use Tourze\MgmCoreBundle\Service\IdGeneratorInterface;
use Tourze\MgmCoreBundle\Service\UlidGenerator;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(UlidGenerator::class)]
#[RunTestsInSeparateProcesses]
class UlidGeneratorTest extends AbstractIntegrationTestCase
{
    private UlidGenerator $ulidGenerator;

    protected function onSetUp(): void
    {
        $this->ulidGenerator = self::getService(UlidGenerator::class);
    }

    public function testGenerateReturnsString(): void
    {
        $result = $this->ulidGenerator->generate();

        $this->assertIsString($result);
    }

    public function testGenerateReturnsNonEmptyString(): void
    {
        $result = $this->ulidGenerator->generate();

        $this->assertNotEmpty($result);
    }

    public function testGenerateReturnsValidUlidFormat(): void
    {
        $result = $this->ulidGenerator->generate();

        // ULID格式：26个字符，包含大写字母和数字，不包含易混淆字符
        $this->assertMatchesRegularExpression('/^[0123456789ABCDEFGHJKMNPQRSTVWXYZ]{26}$/', $result);
        $this->assertSame(26, strlen($result));
    }

    public function testGenerateReturnsValidUlidInstance(): void
    {
        $result = $this->ulidGenerator->generate();

        // 验证生成的字符串可以创建有效的Ulid实例
        $ulid = Ulid::fromString($result);
        $this->assertInstanceOf(Ulid::class, $ulid);
        $this->assertSame($result, (string) $ulid);
    }

    public function testGenerateProducesUniqueIds(): void
    {
        $ids = [];

        // 生成多个ID
        for ($i = 0; $i < 1000; ++$i) {
            $ids[] = $this->ulidGenerator->generate();
        }

        // 验证所有ID都是唯一的
        $uniqueIds = array_unique($ids);
        $this->assertCount(1000, $uniqueIds, 'All generated IDs should be unique');
    }

    public function testGenerateProducesChronologicalIds(): void
    {
        $id1 = $this->ulidGenerator->generate();

        // 确保时间差
        usleep(1000); // 1ms

        $id2 = $this->ulidGenerator->generate();

        // ULID的时间戳部分应该是递增的
        $ulid1 = Ulid::fromString($id1);
        $ulid2 = Ulid::fromString($id2);

        $this->assertLessThanOrEqual($ulid2->getDateTime(), $ulid1->getDateTime());
    }

    public function testGenerateConsistentLength(): void
    {
        // 生成多个ID并验证长度一致性
        for ($i = 0; $i < 100; ++$i) {
            $id = $this->ulidGenerator->generate();
            $this->assertSame(26, strlen($id), 'All ULIDs should have exactly 26 characters');
        }
    }

    public function testGenerateDoesNotContainConfusingCharacters(): void
    {
        // ULID不应包含容易混淆的字符：O、I、L（但可以包含0和1）
        $confusingChars = ['O', 'I', 'L'];

        for ($i = 0; $i < 100; ++$i) {
            $id = $this->ulidGenerator->generate();

            foreach ($confusingChars as $char) {
                $this->assertStringNotContainsString(
                    $char,
                    $id,
                    "ULID should not contain confusing character: {$char}"
                );
            }
        }
    }

    public function testGenerateTimestampAccuracy(): void
    {
        $beforeGeneration = time();
        $id = $this->ulidGenerator->generate();
        $afterGeneration = time();

        $ulid = Ulid::fromString($id);
        $ulidTimestamp = $ulid->getDateTime()->getTimestamp();

        $this->assertGreaterThanOrEqual($beforeGeneration, $ulidTimestamp);
        $this->assertLessThanOrEqual($afterGeneration, $ulidTimestamp);
    }

    public function testGenerateRandomnessInSameMicrosecond(): void
    {
        $ids = [];

        // 快速生成多个ID（可能在同一微秒内）
        for ($i = 0; $i < 100; ++$i) {
            $ids[] = $this->ulidGenerator->generate();
        }

        // 即使在同一时间戳内，由于随机性部分，ID应该不同
        $uniqueIds = array_unique($ids);
        $this->assertCount(100, $uniqueIds, 'IDs should be unique even when generated rapidly');
    }

    public function testImplementsIdGeneratorInterface(): void
    {
        $this->assertInstanceOf(IdGeneratorInterface::class, $this->ulidGenerator);
    }

    public function testServiceIntegration(): void
    {
        // 验证通过服务容器获取的实例
        $serviceInstance = self::getService(IdGeneratorInterface::class);
        $this->assertInstanceOf(UlidGenerator::class, $serviceInstance);

        $id1 = $serviceInstance->generate();
        $id2 = $this->ulidGenerator->generate();

        // 两个实例应该都能正常生成有效ID
        $this->assertMatchesRegularExpression('/^[0123456789ABCDEFGHJKMNPQRSTVWXYZ]{26}$/', $id1);
        $this->assertMatchesRegularExpression('/^[0123456789ABCDEFGHJKMNPQRSTVWXYZ]{26}$/', $id2);
        $this->assertNotSame($id1, $id2);
    }

    public function testGeneratePerformance(): void
    {
        $startTime = microtime(true);

        // 生成大量ID以测试性能
        for ($i = 0; $i < 10000; ++$i) {
            $this->ulidGenerator->generate();
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // 10000个ID应该在合理时间内生成完成（小于1秒）
        $this->assertLessThan(1.0, $executionTime, '10000 IDs should be generated within 1 second');
    }

    public function testGenerateContainsOnlyValidCharacters(): void
    {
        $validChars = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

        for ($i = 0; $i < 50; ++$i) {
            $id = $this->ulidGenerator->generate();

            for ($j = 0; $j < strlen($id); ++$j) {
                $char = $id[$j];
                $this->assertStringContainsString(
                    $char,
                    $validChars,
                    "Character '{$char}' at position {$j} in '{$id}' is not a valid ULID character"
                );
            }
        }
    }

    public function testGenerateTimestampPrecision(): void
    {
        $ids = [];
        $startTime = microtime(true);

        // 在短时间内生成多个ID
        while (microtime(true) - $startTime < 0.001) { // 1ms内
            $ids[] = $this->ulidGenerator->generate();
        }

        // 即使在很短时间内，所有ID应该都不同
        $uniqueIds = array_unique($ids);
        $this->assertCount(count($ids), $uniqueIds, 'All IDs generated within 1ms should be unique');
    }

    public function testGenerateLexicographicalOrder(): void
    {
        $ids = [];

        for ($i = 0; $i < 10; ++$i) {
            $ids[] = $this->ulidGenerator->generate();
            usleep(1000); // 确保时间差
        }

        // 验证生成的ID在字典序上是递增的（由于时间戳递增）
        for ($i = 1; $i < count($ids); ++$i) {
            $this->assertGreaterThanOrEqual(
                $ids[$i - 1],
                $ids[$i],
                'ULIDs should be in lexicographical order when generated sequentially'
            );
        }
    }

    public function testGenerateWithHighVolume(): void
    {
        $batchSize = 1000;
        $batches = 5;
        $allIds = [];

        for ($batch = 0; $batch < $batches; ++$batch) {
            $batchIds = [];

            for ($i = 0; $i < $batchSize; ++$i) {
                $batchIds[] = $this->ulidGenerator->generate();
            }

            // 验证批次内唯一性
            $uniqueBatchIds = array_unique($batchIds);
            $this->assertCount($batchSize, $uniqueBatchIds, "Batch {$batch} should have all unique IDs");

            $allIds = array_merge($allIds, $batchIds);
        }

        // 验证总体唯一性
        $uniqueAllIds = array_unique($allIds);
        $expectedTotal = $batchSize * $batches;
        $this->assertCount($expectedTotal, $uniqueAllIds, "All {$expectedTotal} IDs should be unique across batches");
    }

    public function testGenerateReturnsProperUlidStructure(): void
    {
        $id = $this->ulidGenerator->generate();

        // ULID结构：48位时间戳 + 80位随机数 = 128位 = 26个Base32字符
        // 前10个字符是时间戳部分，后16个字符是随机部分
        $timestampPart = substr($id, 0, 10);
        $randomPart = substr($id, 10, 16);

        $this->assertSame(10, strlen($timestampPart), 'Timestamp part should be 10 characters');
        $this->assertSame(16, strlen($randomPart), 'Random part should be 16 characters');
        $this->assertSame(26, strlen($timestampPart . $randomPart), 'Total length should be 26 characters');
    }

    public function testGenerateCanBeUsedAsEntityId(): void
    {
        // 验证生成的ID可以用作数据库实体ID
        $id = $this->ulidGenerator->generate();

        // 检查是否适合作为数据库主键
        $this->assertIsString($id);
        $this->assertNotEmpty($id);
        $this->assertSame(26, strlen($id)); // 固定长度，便于索引
        $this->assertMatchesRegularExpression('/^[A-Z0-9]+$/', $id); // 只包含大写字母和数字

        // 验证不包含特殊字符，避免SQL注入等问题
        $this->assertStringNotContainsString(' ', $id);
        $this->assertStringNotContainsString('-', $id);
        $this->assertStringNotContainsString('_', $id);
        $this->assertStringNotContainsString("'", $id);
        $this->assertStringNotContainsString('"', $id);
    }
}
