<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\MgmCoreBundle\Entity\Campaign;
use Tourze\MgmCoreBundle\Enum\Attribution;
use Tourze\MgmCoreBundle\Repository\CampaignRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(CampaignRepository::class)]
#[RunTestsInSeparateProcesses]
class CampaignRepositoryTest extends AbstractRepositoryTestCase
{
    protected function onSetUp(): void
    {
    }

    protected function createNewEntity(): object
    {
        $campaign = new Campaign();
        $campaign->setId('zzzz-' . uniqid());
        $campaign->setName('Test Campaign ' . uniqid());
        $campaign->setActive(true);
        $campaign->setConfigJson([]);
        $campaign->setWindowDays(7);
        $campaign->setAttribution(Attribution::FIRST);
        $campaign->setSelfBlock(false);
        $campaign->setBudgetLimit('100.0000');
        $campaign->setCreateTime(new \DateTimeImmutable());
        $campaign->setUpdateTime(new \DateTimeImmutable());

        return $campaign;
    }

    protected function getRepository(): CampaignRepository
    {
        $repository = self::getContainer()->get(CampaignRepository::class);
        self::assertInstanceOf(CampaignRepository::class, $repository);

        return $repository;
    }

    public function testSave(): void
    {
        $campaign = $this->createTestCampaign();

        $this->getRepository()->save($campaign, true);

        $found = $this->getRepository()->find('test-campaign-1');
        $this->assertNotNull($found);
        $this->assertSame('test-campaign-1', $found->getId());
        $this->assertSame('Test Campaign 1', $found->getName());
        $this->assertTrue($found->isActive());
        $this->assertSame(30, $found->getWindowDays());
        $this->assertSame(Attribution::FIRST, $found->getAttribution());
        $this->assertTrue($found->isSelfBlock());
    }

    public function testSaveWithoutFlush(): void
    {
        $campaign = $this->createTestCampaign('test-campaign-no-flush', 'No Flush Campaign');

        $this->getRepository()->save($campaign, false);

        // 手动flush以验证数据已持久化
        self::getEntityManager()->flush();

        $found = $this->getRepository()->find('test-campaign-no-flush');
        $this->assertNotNull($found);
        $this->assertSame('No Flush Campaign', $found->getName());
    }

    public function testRemove(): void
    {
        $campaign = $this->createTestCampaign('test-campaign-remove', 'Campaign to Remove');
        $this->getRepository()->save($campaign, true);

        $this->getRepository()->remove($campaign, true);

        $found = $this->getRepository()->find('test-campaign-remove');
        $this->assertNull($found);
    }

    // testRemoveWithoutFlush() 由基类提供

    public function testFindActiveById(): void
    {
        // 创建激活的活动
        $activeCampaign = $this->createTestCampaign('active-campaign', 'Active Campaign');
        $activeCampaign->setActive(true);
        $this->getRepository()->save($activeCampaign, true);

        // 创建未激活的活动
        $inactiveCampaign = $this->createTestCampaign('inactive-campaign', 'Inactive Campaign');
        $inactiveCampaign->setActive(false);
        $this->getRepository()->save($inactiveCampaign, true);

        // 测试查找激活的活动
        $result = $this->getRepository()->findActiveById('active-campaign');
        $this->assertNotNull($result);
        $this->assertSame('active-campaign', $result->getId());
        $this->assertTrue($result->isActive());

        // 测试查找未激活的活动应返回null
        $result = $this->getRepository()->findActiveById('inactive-campaign');
        $this->assertNull($result);

        // 测试查找不存在的活动应返回null
        $result = $this->getRepository()->findActiveById('non-existent-campaign');
        $this->assertNull($result);
    }

    public function testFindByName(): void
    {
        // 创建多个不同名称的活动
        $campaign1 = $this->createTestCampaign('campaign-1', 'Unique Campaign Name');
        $this->getRepository()->save($campaign1, true);

        $campaign2 = $this->createTestCampaign('campaign-2', 'Another Campaign Name');
        $this->getRepository()->save($campaign2, true);

        // 测试按名称查找
        $result = $this->getRepository()->findByName('Unique Campaign Name');
        $this->assertNotNull($result);
        $this->assertSame('campaign-1', $result->getId());
        $this->assertSame('Unique Campaign Name', $result->getName());

        // 测试查找另一个名称
        $result = $this->getRepository()->findByName('Another Campaign Name');
        $this->assertNotNull($result);
        $this->assertSame('campaign-2', $result->getId());

        // 测试查找不存在的名称
        $result = $this->getRepository()->findByName('Non-existent Campaign');
        $this->assertNull($result);
    }

    public function testFindByNameCaseSensitive(): void
    {
        $campaign = $this->createTestCampaign('case-sensitive', 'Case Sensitive Name');
        $this->getRepository()->save($campaign, true);

        // 测试精确匹配
        $result = $this->getRepository()->findByName('Case Sensitive Name');
        $this->assertNotNull($result);

        // 测试大小写不匹配应返回null
        $result = $this->getRepository()->findByName('case sensitive name');
        $this->assertNull($result);

        $result = $this->getRepository()->findByName('CASE SENSITIVE NAME');
        $this->assertNull($result);
    }

    public function testCampaignWithComplexConfig(): void
    {
        $campaign = $this->createTestCampaign('complex-config', 'Complex Config Campaign');
        $campaign->setConfigJson([
            'rewards' => [
                'referrer' => ['type' => 'points', 'amount' => 100],
                'referee' => ['type' => 'discount', 'amount' => 10],
            ],
            'qualification' => [
                'min_order_amount' => 50.0,
                'categories' => ['electronics', 'books'],
            ],
        ]);
        $campaign->setBudgetLimit('10000.5000');

        $this->getRepository()->save($campaign, true);

        $found = $this->getRepository()->find('complex-config');
        $this->assertNotNull($found);

        $config = $found->getConfigJson();
        $this->assertIsArray($config);
        $this->assertArrayHasKey('rewards', $config);
        $this->assertArrayHasKey('qualification', $config);
        $this->assertIsArray($config['rewards']);
        $this->assertArrayHasKey('referrer', $config['rewards']);
        $this->assertIsArray($config['rewards']['referrer']);
        $this->assertArrayHasKey('amount', $config['rewards']['referrer']);
        $this->assertSame(100, $config['rewards']['referrer']['amount']);
        $this->assertSame('10000.5000', $found->getBudgetLimit());
    }

    public function testCampaignWithDifferentAttributionStrategies(): void
    {
        // 测试FIRST归因策略
        $firstCampaign = $this->createTestCampaign('first-attribution', 'First Attribution');
        $firstCampaign->setAttribution(Attribution::FIRST);
        $this->getRepository()->save($firstCampaign, true);

        // 测试LAST归因策略
        $lastCampaign = $this->createTestCampaign('last-attribution', 'Last Attribution');
        $lastCampaign->setAttribution(Attribution::LAST);
        $this->getRepository()->save($lastCampaign, true);

        $foundFirst = $this->getRepository()->find('first-attribution');
        $this->assertNotNull($foundFirst);
        $this->assertSame(Attribution::FIRST, $foundFirst->getAttribution());

        $foundLast = $this->getRepository()->find('last-attribution');
        $this->assertNotNull($foundLast);
        $this->assertSame(Attribution::LAST, $foundLast->getAttribution());
    }

    public function testCampaignWithNullBudgetLimit(): void
    {
        $campaign = $this->createTestCampaign('no-budget', 'No Budget Campaign');
        $campaign->setBudgetLimit(null);

        $this->getRepository()->save($campaign, true);

        $found = $this->getRepository()->find('no-budget');
        $this->assertNotNull($found);
        $this->assertNull($found->getBudgetLimit());
    }

    public function testCampaignSelfBlockSettings(): void
    {
        // 测试允许自推荐
        $allowSelfCampaign = $this->createTestCampaign('allow-self', 'Allow Self Campaign');
        $allowSelfCampaign->setSelfBlock(false);
        $this->getRepository()->save($allowSelfCampaign, true);

        // 测试禁止自推荐
        $blockSelfCampaign = $this->createTestCampaign('block-self', 'Block Self Campaign');
        $blockSelfCampaign->setSelfBlock(true);
        $this->getRepository()->save($blockSelfCampaign, true);

        $allowFound = $this->getRepository()->find('allow-self');
        $this->assertNotNull($allowFound);
        $this->assertFalse($allowFound->isSelfBlock());

        $blockFound = $this->getRepository()->find('block-self');
        $this->assertNotNull($blockFound);
        $this->assertTrue($blockFound->isSelfBlock());
    }

    private function createTestCampaign(string $id = 'test-campaign-1', string $name = 'Test Campaign 1'): Campaign
    {
        $campaign = new Campaign();
        $campaign->setId($id);
        $campaign->setName($name);
        $campaign->setActive(true);
        $campaign->setConfigJson([]);
        $campaign->setWindowDays(30);
        $campaign->setAttribution(Attribution::FIRST);
        $campaign->setSelfBlock(true);
        $campaign->setBudgetLimit('5000.0000');
        $campaign->setCreateTime(new \DateTimeImmutable());
        $campaign->setUpdateTime(new \DateTimeImmutable());

        return $campaign;
    }
}
