<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\MgmCoreBundle\DependencyInjection\MgmCoreExtension;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;

/**
 * @internal
 */
#[CoversClass(MgmCoreExtension::class)]
final class MgmCoreExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    public function testLoadsServices(): void
    {
        $extension = new MgmCoreExtension();
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        $extension->load([], $container);

        $this->assertNotEmpty($container->getDefinitions());
    }
}
