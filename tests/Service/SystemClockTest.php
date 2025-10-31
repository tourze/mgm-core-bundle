<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\MgmCoreBundle\Service\ClockInterface;
use Tourze\MgmCoreBundle\Service\SystemClock;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(SystemClock::class)]
#[RunTestsInSeparateProcesses]
class SystemClockTest extends AbstractIntegrationTestCase
{
    private SystemClock $systemClock;

    protected function onSetUp(): void
    {
        $this->systemClock = self::getService(SystemClock::class);
    }

    public function testNowReturnsDateTimeInterface(): void
    {
        $result = $this->systemClock->now();

        $this->assertInstanceOf(\DateTimeInterface::class, $result);
    }

    public function testNowReturnsDateTimeImmutable(): void
    {
        $result = $this->systemClock->now();

        $this->assertInstanceOf(\DateTimeImmutable::class, $result);
    }

    public function testNowReturnsCurrentTime(): void
    {
        $beforeCall = time();
        $result = $this->systemClock->now();
        $afterCall = time();

        $resultTimestamp = $result->getTimestamp();

        $this->assertGreaterThanOrEqual($beforeCall, $resultTimestamp);
        $this->assertLessThanOrEqual($afterCall, $resultTimestamp);
    }

    public function testNowReturnsDifferentTimesOnMultipleCalls(): void
    {
        $time1 = $this->systemClock->now();

        // 确保有微小的时间差
        usleep(1000); // 1ms

        $time2 = $this->systemClock->now();

        // 时间应该是不同的（或者至少不早于第一次调用）
        $this->assertGreaterThanOrEqual($time1->getTimestamp(), $time2->getTimestamp());

        // 检查微秒级差异
        if ($time1->getTimestamp() === $time2->getTimestamp()) {
            // 如果秒数相同，微秒应该不同
            $this->assertGreaterThanOrEqual(
                (int) $time1->format('u'),
                (int) $time2->format('u')
            );
        }
    }

    public function testNowTimestampAccuracy(): void
    {
        $systemTimeBefore = microtime(true);
        $clockTime = $this->systemClock->now();
        $systemTimeAfter = microtime(true);

        $clockTimestamp = $clockTime->getTimestamp() + ($clockTime->format('u') / 1000000);

        $this->assertGreaterThanOrEqual($systemTimeBefore, $clockTimestamp);
        $this->assertLessThanOrEqual($systemTimeAfter, $clockTimestamp);
    }

    public function testNowFormatConsistency(): void
    {
        $result = $this->systemClock->now();

        // 验证返回的DateTime对象具有预期的格式
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $result->format('Y-m-d H:i:s'));
        $this->assertMatchesRegularExpression('/^\d{6}$/', $result->format('u')); // 微秒部分
    }

    public function testImplementsClockInterface(): void
    {
        $this->assertInstanceOf(ClockInterface::class, $this->systemClock);
    }

    public function testNowIsNotNull(): void
    {
        $result = $this->systemClock->now();

        $this->assertNotNull($result);
    }

    public function testNowTimezoneHandling(): void
    {
        $result = $this->systemClock->now();

        // 验证时区信息存在
        $this->assertNotNull($result->getTimezone());

        // 获取时区名称，应该是系统默认时区
        $timezone = $result->getTimezone()->getName();
        $this->assertNotEmpty($timezone);
    }

    public function testConsecutiveCallsIncreaseTime(): void
    {
        $times = [];

        // 收集多个时间点
        for ($i = 0; $i < 5; ++$i) {
            $times[] = $this->systemClock->now();
            usleep(1000); // 1ms 间隔
        }

        // 验证时间是递增的（或至少不递减）
        for ($i = 1; $i < count($times); ++$i) {
            $this->assertGreaterThanOrEqual(
                $times[$i - 1]->getTimestamp(),
                $times[$i]->getTimestamp(),
                'Time should not go backwards between calls'
            );
        }
    }

    public function testNowReturnsImmutableDateTime(): void
    {
        $originalTime = $this->systemClock->now();

        // DateTimeImmutable 的 modify 返回新对象，不改变原对象
        $modifiedTime = \DateTimeImmutable::createFromInterface($originalTime)->modify('+1 hour');

        // 原时间不应该被影响
        $this->assertNotSame($originalTime->getTimestamp(), $modifiedTime->getTimestamp());
        $this->assertLessThan($modifiedTime->getTimestamp(), $originalTime->getTimestamp());
        $this->assertInstanceOf(\DateTimeImmutable::class, $originalTime);
    }

    public function testServiceIntegration(): void
    {
        // 验证通过服务容器获取的实例与直接创建的实例行为一致
        $serviceInstance = self::getService(ClockInterface::class);
        $this->assertInstanceOf(SystemClock::class, $serviceInstance);

        $serviceTime = $serviceInstance->now();
        $directTime = $this->systemClock->now();

        // 两个时间应该在合理范围内
        $timeDiff = abs($serviceTime->getTimestamp() - $directTime->getTimestamp());
        $this->assertLessThanOrEqual(1, $timeDiff, 'Service and direct instance should return similar times');
    }

    public function testNowPerformance(): void
    {
        $startTime = microtime(true);

        // 执行多次调用
        for ($i = 0; $i < 1000; ++$i) {
            $this->systemClock->now();
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // 1000次调用应该在合理时间内完成（小于1秒）
        $this->assertLessThan(1.0, $executionTime, '1000 calls should complete within 1 second');
    }

    public function testDateTimeImmutability(): void
    {
        $time1 = $this->systemClock->now();
        $time2 = $this->systemClock->now();

        // 每次调用应该返回新的实例
        $this->assertNotSame($time1, $time2);

        // DateTimeImmutable 的 modify 返回新对象，原对象不变
        $originalTime1Format = $time1->format('Y-m-d H:i:s.u');
        $modifiedTime = \DateTimeImmutable::createFromInterface($time1)->modify('+1 day');

        // 原对象不应该被改变
        $this->assertSame($time1->format('Y-m-d H:i:s.u'), $originalTime1Format);
        // 新对象应该不同
        $this->assertNotSame($modifiedTime->format('Y-m-d H:i:s.u'), $originalTime1Format);
    }

    public function testClockReliabilityUnderLoad(): void
    {
        $times = [];

        // 快速连续调用
        for ($i = 0; $i < 100; ++$i) {
            $times[] = $this->systemClock->now()->getTimestamp();
        }

        // 验证没有时间倒退
        for ($i = 1; $i < count($times); ++$i) {
            $this->assertGreaterThanOrEqual(
                $times[$i - 1],
                $times[$i],
                'Clock should never go backwards, even under load'
            );
        }

        // 验证时间范围合理（整个测试应该在几秒内完成）
        $endTime = end($times);
        $startTime = reset($times);
        if (false !== $endTime && false !== $startTime) {
            $timeSpan = $endTime - $startTime;
            $this->assertLessThanOrEqual(5, $timeSpan, 'All calls should complete within 5 seconds');
        }
    }
}
