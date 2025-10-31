<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\MgmCoreBundle\MgmCoreBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(MgmCoreBundle::class)]
#[RunTestsInSeparateProcesses]
final class MgmCoreBundleTest extends AbstractBundleTestCase
{
}
