<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\MgmCoreBundle\Entity\Campaign;
use Tourze\MgmCoreBundle\Enum\Attribution;

class CampaignFixtures extends Fixture
{
    public const CAMPAIGN_ACTIVE = 'campaign_active';
    public const CAMPAIGN_INACTIVE = 'campaign_inactive';
    public const CAMPAIGN_LIMITED_BUDGET = 'campaign_limited_budget';
    public const CAMPAIGN_NO_SELF_BLOCK = 'campaign_no_self_block';
    public const CAMPAIGN_FIRST_ATTRIBUTION = 'campaign_first_attribution';

    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable();

        // 创建激活的春季推广活动
        $activeCampaign = new Campaign();
        $activeCampaign->setId('campaign-00000000000001');
        $activeCampaign->setName('春季推广活动');
        $activeCampaign->setActive(true);
        $activeCampaign->setWindowDays(7);
        $activeCampaign->setAttribution(Attribution::LAST);
        $activeCampaign->setSelfBlock(true);
        $activeCampaign->setConfigJson([
            'rewards' => [
                'referrer' => ['type' => 'cash', 'amount' => 50, 'currency' => 'CNY'],
                'referee' => ['type' => 'cash', 'amount' => 20, 'currency' => 'CNY'],
            ],
            'rules' => [
                'min_order_amount' => 100,
                'max_rewards_per_referrer' => 10,
            ],
        ]);
        $activeCampaign->setBudgetLimit(null);
        $activeCampaign->setCreateTime($now);
        $activeCampaign->setUpdateTime($now);

        $manager->persist($activeCampaign);
        $this->addReference(self::CAMPAIGN_ACTIVE, $activeCampaign);

        // 创建非激活的暑期活动
        $inactiveCampaign = new Campaign();
        $inactiveCampaign->setId('campaign-00000000000002');
        $inactiveCampaign->setName('暑期推广活动');
        $inactiveCampaign->setActive(false);
        $inactiveCampaign->setWindowDays(14);
        $inactiveCampaign->setAttribution(Attribution::LAST);
        $inactiveCampaign->setSelfBlock(true);
        $inactiveCampaign->setConfigJson([
            'rewards' => [
                'referrer' => ['type' => 'cash', 'amount' => 30, 'currency' => 'CNY'],
                'referee' => ['type' => 'cash', 'amount' => 15, 'currency' => 'CNY'],
            ],
            'rules' => [
                'min_order_amount' => 50,
            ],
        ]);
        $inactiveCampaign->setBudgetLimit(null);
        $inactiveCampaign->setCreateTime($now->modify('-3 months'));
        $inactiveCampaign->setUpdateTime($now->modify('-1 month'));

        $manager->persist($inactiveCampaign);
        $this->addReference(self::CAMPAIGN_INACTIVE, $inactiveCampaign);

        // 创建有预算限制的VIP活动
        $limitedBudgetCampaign = new Campaign();
        $limitedBudgetCampaign->setId('campaign-00000000000003');
        $limitedBudgetCampaign->setName('VIP专享推广活动');
        $limitedBudgetCampaign->setActive(true);
        $limitedBudgetCampaign->setWindowDays(30);
        $limitedBudgetCampaign->setAttribution(Attribution::LAST);
        $limitedBudgetCampaign->setSelfBlock(true);
        $limitedBudgetCampaign->setConfigJson([
            'rewards' => [
                'referrer' => ['type' => 'cash', 'amount' => 100, 'currency' => 'CNY'],
                'referee' => ['type' => 'cash', 'amount' => 50, 'currency' => 'CNY'],
            ],
            'rules' => [
                'min_order_amount' => 500,
                'user_level' => 'vip',
            ],
        ]);
        $limitedBudgetCampaign->setBudgetLimit('10000.0000');
        $limitedBudgetCampaign->setCreateTime($now->modify('-1 month'));
        $limitedBudgetCampaign->setUpdateTime($now);

        $manager->persist($limitedBudgetCampaign);
        $this->addReference(self::CAMPAIGN_LIMITED_BUDGET, $limitedBudgetCampaign);

        // 创建允许自推荐的活动
        $noSelfBlockCampaign = new Campaign();
        $noSelfBlockCampaign->setId('campaign-00000000000004');
        $noSelfBlockCampaign->setName('内部员工推广活动');
        $noSelfBlockCampaign->setActive(true);
        $noSelfBlockCampaign->setWindowDays(365);
        $noSelfBlockCampaign->setAttribution(Attribution::LAST);
        $noSelfBlockCampaign->setSelfBlock(false);
        $noSelfBlockCampaign->setConfigJson([
            'rewards' => [
                'referrer' => ['type' => 'points', 'amount' => 1000],
                'referee' => ['type' => 'points', 'amount' => 500],
            ],
            'rules' => [
                'employee_only' => true,
            ],
        ]);
        $noSelfBlockCampaign->setBudgetLimit(null);
        $noSelfBlockCampaign->setCreateTime($now);
        $noSelfBlockCampaign->setUpdateTime($now);

        $manager->persist($noSelfBlockCampaign);
        $this->addReference(self::CAMPAIGN_NO_SELF_BLOCK, $noSelfBlockCampaign);

        // 创建首次归因策略的活动
        $firstAttributionCampaign = new Campaign();
        $firstAttributionCampaign->setId('campaign-00000000000005');
        $firstAttributionCampaign->setName('首次接触推广活动');
        $firstAttributionCampaign->setActive(true);
        $firstAttributionCampaign->setWindowDays(7);
        $firstAttributionCampaign->setAttribution(Attribution::FIRST);
        $firstAttributionCampaign->setSelfBlock(true);
        $firstAttributionCampaign->setConfigJson([
            'rewards' => [
                'referrer' => ['type' => 'cash', 'amount' => 25, 'currency' => 'CNY'],
                'referee' => ['type' => 'coupon', 'code' => 'FIRST25'],
            ],
            'rules' => [
                'new_user_only' => true,
            ],
        ]);
        $firstAttributionCampaign->setBudgetLimit('5000.0000');
        $firstAttributionCampaign->setCreateTime($now);
        $firstAttributionCampaign->setUpdateTime($now);

        $manager->persist($firstAttributionCampaign);
        $this->addReference(self::CAMPAIGN_FIRST_ATTRIBUTION, $firstAttributionCampaign);

        $manager->flush();
    }
}
