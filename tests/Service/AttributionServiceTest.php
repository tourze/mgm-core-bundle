<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\MgmCoreBundle\DTO\Subject;
use Tourze\MgmCoreBundle\Entity\AttributionToken;
use Tourze\MgmCoreBundle\Entity\Campaign;
use Tourze\MgmCoreBundle\Enum\Attribution;
use Tourze\MgmCoreBundle\Repository\AttributionTokenRepository;
use Tourze\MgmCoreBundle\Service\AttributionService;
use Tourze\MgmCoreBundle\Service\ClockInterface;
use Tourze\MgmCoreBundle\Service\IdGeneratorInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(AttributionService::class)]
#[RunTestsInSeparateProcesses]
class AttributionServiceTest extends AbstractIntegrationTestCase
{
    private AttributionService $attributionService;

    private AttributionTokenRepository $repository;

    private ClockInterface $clock;

    private IdGeneratorInterface $idGenerator;

    protected function onSetUp(): void
    {
        $this->attributionService = self::getService(AttributionService::class);
        $this->repository = self::getService(AttributionTokenRepository::class);
        $this->clock = self::getService(ClockInterface::class);
        $this->idGenerator = self::getService(IdGeneratorInterface::class);
    }

    public function testGenerateToken(): void
    {
        $campaign = $this->createTestCampaign();
        $referrer = new Subject('user', '123');

        $token = $this->attributionService->generateToken($campaign, $referrer);

        $this->assertNotEmpty($token);
        $this->assertIsString($token);

        // 验证token被正确保存到数据库
        $attributionToken = $this->repository->findOneBy(['token' => $token]);
        $this->assertInstanceOf(AttributionToken::class, $attributionToken);
        $this->assertSame($campaign->getId(), $attributionToken->getCampaignId());
        $this->assertSame($referrer->type, $attributionToken->getReferrerType());
        $this->assertSame($referrer->id, $attributionToken->getReferrerId());
        $this->assertNotNull($attributionToken->getExpireTime());
        $this->assertNotNull($attributionToken->getCreateTime());

        // 验证过期时间设置正确（campaign.windowDays + 当前时间）
        $expectedExpireTime = \DateTime::createFromInterface($this->clock->now())
            ->add(new \DateInterval('P' . $campaign->getWindowDays() . 'D'))
        ;
        $actualExpireTime = $attributionToken->getExpireTime();
        $this->assertNotNull($actualExpireTime);

        // 允许1秒的时间误差
        $timeDiff = abs($expectedExpireTime->getTimestamp() - $actualExpireTime->getTimestamp());
        $this->assertLessThanOrEqual(1, $timeDiff);
    }

    public function testGenerateTokenWithDifferentWindowDays(): void
    {
        $campaign = $this->createTestCampaign(['windowDays' => 14]);
        $referrer = new Subject('user', '456');

        $token = $this->attributionService->generateToken($campaign, $referrer);

        $attributionToken = $this->repository->findOneBy(['token' => $token]);

        // 验证14天的窗口期设置
        $expectedExpireTime = \DateTime::createFromInterface($this->clock->now())
            ->add(new \DateInterval('P14D'))
        ;
        $this->assertNotNull($attributionToken);
        $actualExpireTime = $attributionToken->getExpireTime();
        $this->assertNotNull($actualExpireTime);

        $timeDiff = abs($expectedExpireTime->getTimestamp() - $actualExpireTime->getTimestamp());
        $this->assertLessThanOrEqual(1, $timeDiff);
    }

    public function testGenerateTokenWithDifferentSubjectTypes(): void
    {
        $campaign = $this->createTestCampaign();
        $referrer = new Subject('merchant', 'shop_789');

        $token = $this->attributionService->generateToken($campaign, $referrer);

        $attributionToken = $this->repository->findOneBy(['token' => $token]);
        $this->assertNotNull($attributionToken);
        $this->assertSame('merchant', $attributionToken->getReferrerType());
        $this->assertSame('shop_789', $attributionToken->getReferrerId());
    }

    public function testGenerateMultipleTokensAreUnique(): void
    {
        $campaign = $this->createTestCampaign();
        $referrer = new Subject('user', '123');

        $token1 = $this->attributionService->generateToken($campaign, $referrer);
        $token2 = $this->attributionService->generateToken($campaign, $referrer);

        $this->assertNotEmpty($token1);
        $this->assertNotEmpty($token2);
        $this->assertNotSame($token1, $token2);

        // 验证两个token都被保存
        $tokens = $this->repository->findBy(['campaignId' => $campaign->getId()]);
        $this->assertCount(2, $tokens);
    }

    public function testValidateTokenWithValidToken(): void
    {
        $campaign = $this->createTestCampaign();
        $referrer = new Subject('user', '123');

        $token = $this->attributionService->generateToken($campaign, $referrer);

        $attributionToken = $this->attributionService->validateToken($token);

        $this->assertInstanceOf(AttributionToken::class, $attributionToken);
        $this->assertSame($token, $attributionToken->getToken());
        $this->assertSame($campaign->getId(), $attributionToken->getCampaignId());
        $this->assertSame($referrer->type, $attributionToken->getReferrerType());
        $this->assertSame($referrer->id, $attributionToken->getReferrerId());
    }

    public function testValidateTokenWithNonExistentToken(): void
    {
        $result = $this->attributionService->validateToken('non-existent-token');

        $this->assertNull($result);
    }

    public function testValidateTokenWithEmptyToken(): void
    {
        $result = $this->attributionService->validateToken('');

        $this->assertNull($result);
    }

    public function testTokenPersistence(): void
    {
        $campaign = $this->createTestCampaign();
        $referrer = new Subject('user', '123');

        $token = $this->attributionService->generateToken($campaign, $referrer);

        // 清除实体管理器缓存
        self::getEntityManager()->clear();

        // 重新验证token，确保数据已持久化
        $attributionToken = $this->attributionService->validateToken($token);

        $this->assertInstanceOf(AttributionToken::class, $attributionToken);
        $this->assertSame($token, $attributionToken->getToken());
    }

    public function testGenerateTokenTimestampAccuracy(): void
    {
        $beforeGeneration = $this->clock->now();

        $campaign = $this->createTestCampaign();
        $referrer = new Subject('user', '123');
        $token = $this->attributionService->generateToken($campaign, $referrer);

        $afterGeneration = $this->clock->now();

        $attributionToken = $this->repository->findOneBy(['token' => $token]);
        $this->assertNotNull($attributionToken);
        $createTime = $attributionToken->getCreateTime();
        $this->assertNotNull($createTime);

        // 验证创建时间在合理范围内
        $this->assertGreaterThanOrEqual($beforeGeneration->getTimestamp(), $createTime->getTimestamp());
        $this->assertLessThanOrEqual($afterGeneration->getTimestamp(), $createTime->getTimestamp());
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createTestCampaign(array $config = []): Campaign
    {
        $defaultConfig = [
            'name' => 'Test Campaign',
            'active' => true,
            'windowDays' => 7,
            'attribution' => 'last',
            'selfBlock' => true,
        ];

        $config = array_merge($defaultConfig, $config);

        $campaign = new Campaign();
        $campaign->setId($this->idGenerator->generate());
        $name = $config['name'];
        $this->assertIsString($name);
        $campaign->setName($name);

        $active = $config['active'];
        $this->assertIsBool($active);
        $campaign->setActive($active);

        $campaign->setConfigJson($config);

        $windowDays = $config['windowDays'];
        $this->assertIsInt($windowDays);
        $campaign->setWindowDays($windowDays);

        $attribution = $config['attribution'];
        $this->assertIsString($attribution);
        $campaign->setAttribution(Attribution::from($attribution));

        $selfBlock = $config['selfBlock'];
        $this->assertIsBool($selfBlock);
        $campaign->setSelfBlock($selfBlock);

        self::getEntityManager()->persist($campaign);
        self::getEntityManager()->flush();

        return $campaign;
    }
}
