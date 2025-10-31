<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\Service;

use Knp\Menu\ItemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\MgmCoreBundle\Service\AdminMenu;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;

/**
 * 测试 AdminMenu 服务
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
final class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    protected function onSetUp(): void
    {
        // 此测试不需要特殊的设置，AdminMenu 是纯逻辑服务
    }

    public function testInvoke(): void
    {
        $linkGenerator = $this->createMock(LinkGeneratorInterface::class);
        $linkGenerator->expects($this->exactly(7))
            ->method('getCurdListPage')
            ->willReturnCallback(function (string $entityClass) {
                return "/admin/crud/{$entityClass}";
            })
        ;

        $menu = $this->createMock(ItemInterface::class);
        $childMenu = $this->createMock(ItemInterface::class);
        $subMenu = $this->createMock(ItemInterface::class);

        // Mock getChild call to return null first time
        $menu->expects($this->exactly(2))
            ->method('getChild')
            ->with('MGM营销管理')
            ->willReturnOnConsecutiveCalls(null, $childMenu)
        ;

        // Mock addChild call to create main menu
        $menu->expects($this->once())
            ->method('addChild')
            ->with('MGM营销管理')
            ->willReturn($childMenu)
        ;

        // Mock setAttribute call on the created child menu
        $childMenu->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-handshake')
            ->willReturn($childMenu)
        ;

        // Mock all the submenu additions
        $childMenu->expects($this->exactly(7))
            ->method('addChild')
            ->willReturn($subMenu)
        ;

        // Mock setUri and setAttribute calls on submenus
        $subMenu->expects($this->exactly(7))
            ->method('setUri')
            ->willReturn($subMenu)
        ;

        $subMenu->expects($this->exactly(14)) // 7 icon + 7 help
            ->method('setAttribute')
            ->willReturn($subMenu)
        ;

        // 测试时从容器获取AdminMenu服务并设置Mock的LinkGenerator
        self::getContainer()->set(LinkGeneratorInterface::class, $linkGenerator);
        $adminMenu = self::getService(AdminMenu::class);

        $adminMenu($menu);

        // AdminMenu调用成功，无需检查返回值
        $this->assertTrue(true);
    }

    public function testInvokeWithoutLinkGenerator(): void
    {
        // 测试无LinkGenerator的行为
        // 注意：这是一个边界情况测试，需要直接实例化来测试null LinkGenerator的行为（特殊情况）
        // @phpstan-ignore-next-line integrationTest.noDirectInstantiationOfCoveredClass
        $adminMenu = new AdminMenu(null);
        $menu = $this->createMock(ItemInterface::class);

        // 当LinkGenerator为null时，应该直接返回，不执行任何菜单操作
        $menu->expects($this->never())
            ->method('addChild')
        ;
        $menu->expects($this->never())
            ->method('getChild')
        ;

        $adminMenu($menu);

        // AdminMenu调用成功，无需检查返回值
        $this->assertTrue(true);
    }
}
