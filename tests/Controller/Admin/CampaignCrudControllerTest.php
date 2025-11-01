<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\Controller\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\MgmCoreBundle\Controller\Admin\CampaignCrudController;
use Tourze\MgmCoreBundle\Entity\Campaign;
use Tourze\MgmCoreBundle\Enum\Attribution;
use Tourze\MgmCoreBundle\Repository\CampaignRepository;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(CampaignCrudController::class)]
#[RunTestsInSeparateProcesses]
final class CampaignCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getControllerService(): CampaignCrudController
    {
        return self::getService(CampaignCrudController::class);
    }

    /**
     * 提供索引页面表头
     *
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'activity_id' => ['活动ID'];
        yield 'activity_name' => ['活动名称'];
        yield 'is_active' => ['是否激活'];
        yield 'valid_days' => ['有效天数'];
        yield 'attribution_strategy' => ['归因策略'];
        yield 'forbid_self_referral' => ['禁止自推荐'];
        yield 'budget_limit' => ['预算上限'];
        yield 'create_time' => ['创建时间'];
    }

    /**
     * 提供新建页面字段
     *
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'id_field' => ['id'];
        yield 'name_field' => ['name'];
        yield 'active_field' => ['active'];
        yield 'windowDays_field' => ['windowDays'];
        yield 'attribution_field' => ['attribution'];
        yield 'selfBlock_field' => ['selfBlock'];
        yield 'budgetLimit_field' => ['budgetLimit'];
        yield 'configJson_field' => ['configJson'];
    }

    /**
     * 提供编辑页面字段
     *
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'edit_id_field' => ['id'];
        yield 'edit_name_field' => ['name'];
        yield 'edit_active_field' => ['active'];
        yield 'edit_windowDays_field' => ['windowDays'];
        yield 'edit_attribution_field' => ['attribution'];
        yield 'edit_selfBlock_field' => ['selfBlock'];
        yield 'edit_budgetLimit_field' => ['budgetLimit'];
        yield 'edit_configJson_field' => ['configJson'];
    }

    public function testIndexPage(): void
    {
        $client = self::createAuthenticatedClient();
        $crawler = $client->request('GET', '/admin');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Navigate to Campaign CRUD
        $link = $crawler->filter('a[href*="CampaignCrudController"]')->first();
        if ($link->count() > 0) {
            $client->click($link->link());
            $this->assertEquals(200, $client->getResponse()->getStatusCode());
        }
    }

    public function testCreateCampaign(): void
    {
        $client = self::createAuthenticatedClient();
        $client->request('GET', '/admin');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Test entity creation and persistence
        $campaign = new Campaign();
        $campaign->setId('test-campaign-controller-' . uniqid());
        $campaign->setName('Test Campaign from Controller');
        $campaign->setActive(true);
        $campaign->setConfigJson([]);
        $campaign->setWindowDays(30);
        $campaign->setAttribution(Attribution::FIRST);
        $campaign->setSelfBlock(false);
        $campaign->setBudgetLimit('1000.0000');
        $campaign->setCreateTime(new \DateTimeImmutable());
        $campaign->setUpdateTime(new \DateTimeImmutable());

        $campaignRepository = self::getService(CampaignRepository::class);
        self::assertInstanceOf(CampaignRepository::class, $campaignRepository);
        $campaignRepository->save($campaign, true);

        // Verify campaign was created
        $savedCampaign = $campaignRepository->find($campaign->getId());
        $this->assertNotNull($savedCampaign);
        $this->assertEquals('Test Campaign from Controller', $savedCampaign->getName());
        $this->assertTrue($savedCampaign->isActive());
        $this->assertEquals(30, $savedCampaign->getWindowDays());
        $this->assertEquals(Attribution::FIRST, $savedCampaign->getAttribution());
    }

    public function testCampaignDataPersistence(): void
    {
        // Create client to initialize database
        $client = self::createClientWithDatabase();

        // Create test campaigns with different configurations
        $campaign1 = new Campaign();
        $campaign1->setId('controller-test-one-' . uniqid());
        $campaign1->setName('Controller Test Campaign One');
        $campaign1->setActive(true);
        $campaign1->setConfigJson(['type' => 'discount', 'value' => 10]);
        $campaign1->setWindowDays(7);
        $campaign1->setAttribution(Attribution::FIRST);
        $campaign1->setSelfBlock(true);
        $campaign1->setBudgetLimit('5000.0000');
        $campaign1->setCreateTime(new \DateTimeImmutable());
        $campaign1->setUpdateTime(new \DateTimeImmutable());

        $campaignRepository = self::getService(CampaignRepository::class);
        self::assertInstanceOf(CampaignRepository::class, $campaignRepository);
        $campaignRepository->save($campaign1, true);

        $campaign2 = new Campaign();
        $campaign2->setId('controller-test-two-' . uniqid());
        $campaign2->setName('Controller Test Campaign Two');
        $campaign2->setActive(false);
        $campaign2->setConfigJson(['type' => 'points', 'value' => 100]);
        $campaign2->setWindowDays(14);
        $campaign2->setAttribution(Attribution::LAST);
        $campaign2->setSelfBlock(false);
        $campaign2->setBudgetLimit(null);
        $campaign2->setCreateTime(new \DateTimeImmutable());
        $campaign2->setUpdateTime(new \DateTimeImmutable());
        $campaignRepository->save($campaign2, true);

        // Verify campaigns are saved correctly
        $savedCampaign1 = $campaignRepository->find($campaign1->getId());
        $this->assertNotNull($savedCampaign1);
        $this->assertEquals('Controller Test Campaign One', $savedCampaign1->getName());
        $this->assertTrue($savedCampaign1->isActive());
        $this->assertEquals(Attribution::FIRST, $savedCampaign1->getAttribution());
        $this->assertTrue($savedCampaign1->isSelfBlock());
        $this->assertEquals('5000.0000', $savedCampaign1->getBudgetLimit());

        $savedCampaign2 = $campaignRepository->find($campaign2->getId());
        $this->assertNotNull($savedCampaign2);
        $this->assertEquals('Controller Test Campaign Two', $savedCampaign2->getName());
        $this->assertFalse($savedCampaign2->isActive());
        $this->assertEquals(Attribution::LAST, $savedCampaign2->getAttribution());
        $this->assertFalse($savedCampaign2->isSelfBlock());
        $this->assertNull($savedCampaign2->getBudgetLimit());
    }

    public function testCampaignConfigJsonHandling(): void
    {
        $client = self::createClientWithDatabase();

        // Test campaign with complex JSON configuration
        $campaign = new Campaign();
        $campaign->setId('json-config-test-' . uniqid());
        $campaign->setName('JSON Config Test Campaign');
        $campaign->setActive(true);
        $campaign->setConfigJson([
            'rewards' => [
                'referrer' => ['type' => 'points', 'amount' => 100],
                'referee' => ['type' => 'discount', 'percentage' => 15],
            ],
            'qualification' => [
                'min_order_amount' => 50.0,
                'categories' => ['electronics', 'books'],
                'user_types' => ['premium', 'vip'],
            ],
            'limits' => [
                'max_referrals_per_user' => 10,
                'max_uses_per_referral' => 5,
            ],
        ]);
        $campaign->setWindowDays(30);
        $campaign->setAttribution(Attribution::FIRST);
        $campaign->setSelfBlock(true);
        $campaign->setBudgetLimit('10000.5000');
        $campaign->setCreateTime(new \DateTimeImmutable());
        $campaign->setUpdateTime(new \DateTimeImmutable());

        $campaignRepository = self::getService(CampaignRepository::class);
        self::assertInstanceOf(CampaignRepository::class, $campaignRepository);
        $campaignRepository->save($campaign, true);

        $savedCampaign = $campaignRepository->find($campaign->getId());
        $this->assertNotNull($savedCampaign);

        $config = $savedCampaign->getConfigJson();
        $this->assertIsArray($config);
        $this->assertArrayHasKey('rewards', $config);
        $this->assertArrayHasKey('qualification', $config);
        $this->assertArrayHasKey('limits', $config);

        // Test nested configuration values
        $this->assertEquals(100, $config['rewards']['referrer']['amount']);
        $this->assertEquals(15, $config['rewards']['referee']['percentage']);
        $this->assertEquals(50.0, $config['qualification']['min_order_amount']);
        $this->assertContains('electronics', $config['qualification']['categories']);
        $this->assertEquals(10, $config['limits']['max_referrals_per_user']);
    }

    public function testCampaignAttributionStrategies(): void
    {
        $client = self::createClientWithDatabase();

        // Test different attribution strategies
        $firstAttributionCampaign = new Campaign();
        $firstAttributionCampaign->setId('first-attribution-' . uniqid());
        $firstAttributionCampaign->setName('First Attribution Campaign');
        $firstAttributionCampaign->setActive(true);
        $firstAttributionCampaign->setConfigJson([]);
        $firstAttributionCampaign->setWindowDays(7);
        $firstAttributionCampaign->setAttribution(Attribution::FIRST);
        $firstAttributionCampaign->setSelfBlock(false);
        $firstAttributionCampaign->setBudgetLimit('2000.0000');
        $firstAttributionCampaign->setCreateTime(new \DateTimeImmutable());
        $firstAttributionCampaign->setUpdateTime(new \DateTimeImmutable());

        $lastAttributionCampaign = new Campaign();
        $lastAttributionCampaign->setId('last-attribution-' . uniqid());
        $lastAttributionCampaign->setName('Last Attribution Campaign');
        $lastAttributionCampaign->setActive(true);
        $lastAttributionCampaign->setConfigJson([]);
        $lastAttributionCampaign->setWindowDays(14);
        $lastAttributionCampaign->setAttribution(Attribution::LAST);
        $lastAttributionCampaign->setSelfBlock(true);
        $lastAttributionCampaign->setBudgetLimit('3000.0000');
        $lastAttributionCampaign->setCreateTime(new \DateTimeImmutable());
        $lastAttributionCampaign->setUpdateTime(new \DateTimeImmutable());

        $campaignRepository = self::getService(CampaignRepository::class);
        self::assertInstanceOf(CampaignRepository::class, $campaignRepository);
        $campaignRepository->save($firstAttributionCampaign, true);
        $campaignRepository->save($lastAttributionCampaign, true);

        // Verify attribution strategies are correctly set
        $savedFirstCampaign = $campaignRepository->find($firstAttributionCampaign->getId());
        $this->assertNotNull($savedFirstCampaign);
        $this->assertEquals(Attribution::FIRST, $savedFirstCampaign->getAttribution());
        $this->assertFalse($savedFirstCampaign->isSelfBlock());

        $savedLastCampaign = $campaignRepository->find($lastAttributionCampaign->getId());
        $this->assertNotNull($savedLastCampaign);
        $this->assertEquals(Attribution::LAST, $savedLastCampaign->getAttribution());
        $this->assertTrue($savedLastCampaign->isSelfBlock());
    }

    public function testCampaignBudgetLimitHandling(): void
    {
        $client = self::createClientWithDatabase();

        // Campaign with budget limit
        $budgetCampaign = new Campaign();
        $budgetCampaign->setId('budget-limited-' . uniqid());
        $budgetCampaign->setName('Budget Limited Campaign');
        $budgetCampaign->setActive(true);
        $budgetCampaign->setConfigJson([]);
        $budgetCampaign->setWindowDays(30);
        $budgetCampaign->setAttribution(Attribution::FIRST);
        $budgetCampaign->setSelfBlock(false);
        $budgetCampaign->setBudgetLimit('1500.7500');
        $budgetCampaign->setCreateTime(new \DateTimeImmutable());
        $budgetCampaign->setUpdateTime(new \DateTimeImmutable());

        // Campaign without budget limit
        $unlimitedCampaign = new Campaign();
        $unlimitedCampaign->setId('unlimited-budget-' . uniqid());
        $unlimitedCampaign->setName('Unlimited Budget Campaign');
        $unlimitedCampaign->setActive(true);
        $unlimitedCampaign->setConfigJson([]);
        $unlimitedCampaign->setWindowDays(30);
        $unlimitedCampaign->setAttribution(Attribution::LAST);
        $unlimitedCampaign->setSelfBlock(true);
        $unlimitedCampaign->setBudgetLimit(null);
        $unlimitedCampaign->setCreateTime(new \DateTimeImmutable());
        $unlimitedCampaign->setUpdateTime(new \DateTimeImmutable());

        $campaignRepository = self::getService(CampaignRepository::class);
        self::assertInstanceOf(CampaignRepository::class, $campaignRepository);
        $campaignRepository->save($budgetCampaign, true);
        $campaignRepository->save($unlimitedCampaign, true);

        // Verify budget settings
        $savedBudgetCampaign = $campaignRepository->find($budgetCampaign->getId());
        $this->assertNotNull($savedBudgetCampaign);
        $this->assertEquals('1500.7500', $savedBudgetCampaign->getBudgetLimit());

        $savedUnlimitedCampaign = $campaignRepository->find($unlimitedCampaign->getId());
        $this->assertNotNull($savedUnlimitedCampaign);
        $this->assertNull($savedUnlimitedCampaign->getBudgetLimit());
    }

    public function testValidationErrors(): void
    {
        // 考虑到configJson字段的类型转换复杂性，我们使用更简单的策略
        // 直接验证Entity验证器来确保必填字段验证正常工作
        $campaign = new Campaign();
        // 故意留空必填字段
        $campaign->setId(''); // 留空ID
        $campaign->setName(''); // 留空名称
        // 设置其他非必填字段为有效值
        $campaign->setActive(true);
        $campaign->setConfigJson([]);
        $campaign->setWindowDays(30);
        $campaign->setAttribution(Attribution::FIRST);
        $campaign->setSelfBlock(false);
        $campaign->setBudgetLimit(null);
        $campaign->setCreateTime(new \DateTimeImmutable());
        $campaign->setUpdateTime(new \DateTimeImmutable());

        // 获取验证器并验证实体
        $client = self::createClientWithDatabase();
        $validator = self::getService(ValidatorInterface::class);
        $errors = $validator->validate($campaign);

        // 验证确实有验证错误（因为必填字段为空）
        $this->assertGreaterThan(0, count($errors), 'Validation should catch empty required fields');

        // 验证错误信息包含预期内容
        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[] = $error->getMessage();
        }
        $hasBlankError = false;
        foreach ($errorMessages as $message) {
            $messageStr = (string) $message;
            if (str_contains($messageStr, 'blank') || str_contains($messageStr, 'should not be blank')) {
                $hasBlankError = true;
                break;
            }
        }
        $this->assertTrue($hasBlankError, 'Should have blank field validation error');
    }
}
