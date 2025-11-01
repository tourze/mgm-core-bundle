<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\Controller\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\MgmCoreBundle\Controller\Admin\AttributionTokenCrudController;
use Tourze\MgmCoreBundle\Entity\AttributionToken;
use Tourze\MgmCoreBundle\Repository\AttributionTokenRepository;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(AttributionTokenCrudController::class)]
#[RunTestsInSeparateProcesses]
final class AttributionTokenCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getControllerService(): AttributionTokenCrudController
    {
        return self::getService(AttributionTokenCrudController::class);
    }

    /**
     * 提供索引页面表头
     *
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'attribution_token' => ['归因令牌'];
        yield 'campaign_id' => ['活动ID'];
        yield 'referrer_type' => ['推荐人类型'];
        yield 'referrer_id' => ['推荐人ID'];
        yield 'expire_time' => ['过期时间'];
        yield 'create_time' => ['创建时间'];
    }

    /**
     * 提供新建页面字段（返回虚拟项以避免DataProvider错误）
     *
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'disabled' => ['disabled'];
    }

    /**
     * 提供编辑页面字段（返回虚拟项以避免DataProvider错误）
     *
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'disabled' => ['disabled'];
    }

    public function testIndexPage(): void
    {
        $client = self::createAuthenticatedClient();
        $crawler = $client->request('GET', '/admin');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Navigate to AttributionToken CRUD
        $link = $crawler->filter('a[href*="AttributionTokenCrudController"]')->first();
        if ($link->count() > 0) {
            $client->click($link->link());
            $this->assertEquals(200, $client->getResponse()->getStatusCode());
        }
    }

    public function testCreateAttributionToken(): void
    {
        $client = self::createAuthenticatedClient();
        $client->request('GET', '/admin');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Test entity creation and persistence
        $token = new AttributionToken();
        $token->setToken('test-token-' . uniqid());
        $token->setCampaignId('test-campaign-1');
        $token->setReferrerType('user');
        $token->setReferrerId('user-123');
        $token->setExpireTime(new \DateTimeImmutable('+7 days'));
        $token->setCreateTime(new \DateTimeImmutable());

        $tokenRepository = self::getService(AttributionTokenRepository::class);
        self::assertInstanceOf(AttributionTokenRepository::class, $tokenRepository);
        $tokenRepository->save($token, true);

        // Verify token was created
        $savedToken = $tokenRepository->find($token->getToken());
        $this->assertNotNull($savedToken);
        $this->assertEquals('test-campaign-1', $savedToken->getCampaignId());
        $this->assertEquals('user', $savedToken->getReferrerType());
        $this->assertEquals('user-123', $savedToken->getReferrerId());
    }

    public function testAttributionTokenDataPersistence(): void
    {
        // Create client to initialize database
        $client = self::createClientWithDatabase();

        // Create test tokens with different configurations
        $token1 = new AttributionToken();
        $token1->setToken('token-test-one-' . uniqid());
        $token1->setCampaignId('campaign-a');
        $token1->setReferrerType('user');
        $token1->setReferrerId('referrer-1');
        $token1->setExpireTime(new \DateTimeImmutable('+1 week'));
        $token1->setCreateTime(new \DateTimeImmutable());

        $tokenRepository = self::getService(AttributionTokenRepository::class);
        self::assertInstanceOf(AttributionTokenRepository::class, $tokenRepository);
        $tokenRepository->save($token1, true);

        $token2 = new AttributionToken();
        $token2->setToken('token-test-two-' . uniqid());
        $token2->setCampaignId('campaign-b');
        $token2->setReferrerType('agent');
        $token2->setReferrerId('agent-1');
        $token2->setExpireTime(new \DateTimeImmutable('+2 weeks'));
        $token2->setCreateTime(new \DateTimeImmutable());
        $tokenRepository->save($token2, true);

        // Verify tokens are saved correctly
        $savedToken1 = $tokenRepository->find($token1->getToken());
        $this->assertNotNull($savedToken1);
        $this->assertEquals('campaign-a', $savedToken1->getCampaignId());
        $this->assertEquals('user', $savedToken1->getReferrerType());
        $this->assertEquals('referrer-1', $savedToken1->getReferrerId());

        $savedToken2 = $tokenRepository->find($token2->getToken());
        $this->assertNotNull($savedToken2);
        $this->assertEquals('campaign-b', $savedToken2->getCampaignId());
        $this->assertEquals('agent', $savedToken2->getReferrerType());
        $this->assertEquals('agent-1', $savedToken2->getReferrerId());
    }

    public function testTokenExpiration(): void
    {
        $client = self::createClientWithDatabase();

        // Create expired token
        $expiredToken = new AttributionToken();
        $expiredToken->setToken('expired-token-' . uniqid());
        $expiredToken->setCampaignId('test-campaign');
        $expiredToken->setReferrerType('user');
        $expiredToken->setReferrerId('user-expired');
        $expiredToken->setExpireTime(new \DateTimeImmutable('-1 day')); // Yesterday
        $expiredToken->setCreateTime(new \DateTimeImmutable('-2 days'));

        // Create valid token
        $validToken = new AttributionToken();
        $validToken->setToken('valid-token-' . uniqid());
        $validToken->setCampaignId('test-campaign');
        $validToken->setReferrerType('user');
        $validToken->setReferrerId('user-valid');
        $validToken->setExpireTime(new \DateTimeImmutable('+1 day')); // Tomorrow
        $validToken->setCreateTime(new \DateTimeImmutable());

        $tokenRepository = self::getService(AttributionTokenRepository::class);
        self::assertInstanceOf(AttributionTokenRepository::class, $tokenRepository);
        $tokenRepository->save($expiredToken, true);
        $tokenRepository->save($validToken, true);

        // Verify both tokens are saved but can be differentiated by expiration time
        $savedExpiredToken = $tokenRepository->find($expiredToken->getToken());
        $this->assertNotNull($savedExpiredToken);
        $this->assertLessThan(new \DateTimeImmutable(), $savedExpiredToken->getExpireTime());

        $savedValidToken = $tokenRepository->find($validToken->getToken());
        $this->assertNotNull($savedValidToken);
        $this->assertGreaterThan(new \DateTimeImmutable(), $savedValidToken->getExpireTime());
    }

    public function testTokenStringRepresentation(): void
    {
        $client = self::createClientWithDatabase();

        $token = new AttributionToken();
        $tokenValue = 'string-test-' . uniqid();
        $token->setToken($tokenValue);
        $token->setCampaignId('test-campaign');
        $token->setReferrerType('user');
        $token->setReferrerId('user-string');
        $token->setExpireTime(new \DateTimeImmutable('+1 day'));
        $token->setCreateTime(new \DateTimeImmutable());

        // Test toString method
        $this->assertEquals($tokenValue, (string) $token);
        $this->assertEquals($tokenValue, $token->getId());

        $tokenRepository = self::getService(AttributionTokenRepository::class);
        self::assertInstanceOf(AttributionTokenRepository::class, $tokenRepository);
        $tokenRepository->save($token, true);

        $savedToken = $tokenRepository->find($tokenValue);
        $this->assertNotNull($savedToken);
        $this->assertEquals($tokenValue, (string) $savedToken);
    }
}
