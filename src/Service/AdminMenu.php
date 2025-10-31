<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Service;

use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\MgmCoreBundle\Entity\AttributionToken;
use Tourze\MgmCoreBundle\Entity\Campaign;
use Tourze\MgmCoreBundle\Entity\IdempotencyKey;
use Tourze\MgmCoreBundle\Entity\Ledger;
use Tourze\MgmCoreBundle\Entity\Qualification;
use Tourze\MgmCoreBundle\Entity\Referral;
use Tourze\MgmCoreBundle\Entity\Reward;

#[Autoconfigure(public: true)]
readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(
        private ?LinkGeneratorInterface $linkGenerator = null,
    ) {
    }

    public function __invoke(ItemInterface $item): void
    {
        if (null === $this->linkGenerator) {
            return;
        }

        if (null === $item->getChild('MGM营销管理')) {
            $item->addChild('MGM营销管理')
                ->setAttribute('icon', 'fas fa-handshake')
            ;
        }

        $mgmMenu = $item->getChild('MGM营销管理');

        if (null === $mgmMenu) {
            return;
        }

        // MGM活动管理菜单
        $mgmMenu->addChild('MGM活动')
            ->setUri($this->linkGenerator->getCurdListPage(Campaign::class))
            ->setAttribute('icon', 'fas fa-bullhorn')
            ->setAttribute('help', '管理MGM推荐活动配置')
        ;

        // 推荐关系菜单
        $mgmMenu->addChild('推荐关系')
            ->setUri($this->linkGenerator->getCurdListPage(Referral::class))
            ->setAttribute('icon', 'fas fa-users')
            ->setAttribute('help', '查看用户间的推荐关系')
        ;

        // 资格审核菜单
        $mgmMenu->addChild('资格审核')
            ->setUri($this->linkGenerator->getCurdListPage(Qualification::class))
            ->setAttribute('icon', 'fas fa-check-circle')
            ->setAttribute('help', '管理推荐关系的资格审核')
        ;

        // 奖励记录菜单
        $mgmMenu->addChild('奖励记录')
            ->setUri($this->linkGenerator->getCurdListPage(Reward::class))
            ->setAttribute('icon', 'fas fa-gift')
            ->setAttribute('help', '查看奖励发放记录')
        ;

        // 账本记录菜单
        $mgmMenu->addChild('账本记录')
            ->setUri($this->linkGenerator->getCurdListPage(Ledger::class))
            ->setAttribute('icon', 'fas fa-book')
            ->setAttribute('help', '查看奖励金额变动记录')
        ;

        // 归因令牌菜单
        $mgmMenu->addChild('归因令牌')
            ->setUri($this->linkGenerator->getCurdListPage(AttributionToken::class))
            ->setAttribute('icon', 'fas fa-ticket-alt')
            ->setAttribute('help', '查看推荐归因令牌')
        ;

        // 幂等性键菜单
        $mgmMenu->addChild('幂等性键')
            ->setUri($this->linkGenerator->getCurdListPage(IdempotencyKey::class))
            ->setAttribute('icon', 'fas fa-key')
            ->setAttribute('help', '查看系统幂等性键记录')
        ;
    }
}
