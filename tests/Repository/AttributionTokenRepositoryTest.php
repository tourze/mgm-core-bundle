<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\MgmCoreBundle\Entity\AttributionToken;
use Tourze\MgmCoreBundle\Repository\AttributionTokenRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(AttributionTokenRepository::class)]
#[RunTestsInSeparateProcesses]
class AttributionTokenRepositoryTest extends AbstractRepositoryTestCase
{
    protected function onSetUp(): void
    {
    }

    protected function createNewEntity(): object
    {
        $token = new AttributionToken();
        $token->setToken('zzzz-' . uniqid());
        $token->setCampaignId('campaign-1');
        $token->setReferrerType('user');
        $token->setReferrerId('referrer-' . uniqid());
        $token->setExpireTime(new \DateTimeImmutable('+1 hour'));
        $token->setCreateTime(new \DateTimeImmutable());

        return $token;
    }

    protected function getRepository(): AttributionTokenRepository
    {
        $repository = self::getContainer()->get(AttributionTokenRepository::class);
        self::assertInstanceOf(AttributionTokenRepository::class, $repository);

        return $repository;
    }

    public function testSave(): void
    {
        $token = $this->createTestToken();

        $this->getRepository()->save($token, true);

        $found = $this->getRepository()->findOneBy(['token' => 'test-token-123']);
        $this->assertNotNull($found);
        $this->assertSame('test-token-123', $found->getToken());
        $this->assertSame('campaign-1', $found->getCampaignId());
        $this->assertSame('user', $found->getReferrerType());
        $this->assertStringStartsWith('referrer-', $found->getReferrerId());
    }

    public function testSaveWithoutFlush(): void
    {
        $token = $this->createTestToken('token-no-flush');

        $this->getRepository()->save($token, false);

        // 手动flush以验证数据已持久化
        self::getEntityManager()->flush();

        $found = $this->getRepository()->findOneBy(['token' => 'token-no-flush']);
        $this->assertNotNull($found);
    }

    public function testRemove(): void
    {
        $token = $this->createTestToken('token-to-remove');
        $this->getRepository()->save($token, true);

        $this->getRepository()->remove($token, true);

        $found = $this->getRepository()->findOneBy(['token' => 'token-to-remove']);
        $this->assertNull($found);
    }

    // testRemoveWithoutFlush() 由基类提供

    public function testFindValidToken(): void
    {
        // 创建一个有效的token（未过期）
        $validToken = $this->createTestToken('valid-token', new \DateTimeImmutable('+1 hour'));
        $this->getRepository()->save($validToken, true);

        // 创建一个过期的token
        $expiredToken = $this->createTestToken('expired-token', new \DateTimeImmutable('-1 hour'));
        $this->getRepository()->save($expiredToken, true);

        // 测试查找有效token
        $result = $this->getRepository()->findValidToken('valid-token');
        $this->assertNotNull($result);
        $this->assertSame('valid-token', $result->getToken());

        // 测试查找过期token应返回null
        $result = $this->getRepository()->findValidToken('expired-token');
        $this->assertNull($result);

        // 测试查找不存在的token应返回null
        $result = $this->getRepository()->findValidToken('non-existent-token');
        $this->assertNull($result);
    }

    public function testFindByCampaignAndReferrer(): void
    {
        // 创建多个token，部分匹配条件
        $token1 = $this->createTestToken('token-1');
        $token1->setCampaignId('campaign-match');
        $token1->setReferrerType('user');
        $token1->setReferrerId('referrer-match');
        $this->getRepository()->save($token1, true);

        $token2 = $this->createTestToken('token-2');
        $token2->setCampaignId('campaign-match');
        $token2->setReferrerType('user');
        $token2->setReferrerId('referrer-match');
        $this->getRepository()->save($token2, true);

        $token3 = $this->createTestToken('token-3');
        $token3->setCampaignId('campaign-different');
        $token3->setReferrerType('user');
        $token3->setReferrerId('referrer-match');
        $this->getRepository()->save($token3, true);

        $token4 = $this->createTestToken('token-4');
        $token4->setCampaignId('campaign-match');
        $token4->setReferrerType('admin');
        $token4->setReferrerId('referrer-match');
        $this->getRepository()->save($token4, true);

        // 查找匹配的token
        $results = $this->getRepository()->findByCampaignAndReferrer(
            'campaign-match',
            'user',
            'referrer-match'
        );

        $this->assertCount(2, $results);
        $tokenValues = array_map(fn (AttributionToken $t) => $t->getToken(), $results);
        $this->assertContains('token-1', $tokenValues);
        $this->assertContains('token-2', $tokenValues);

        // 查找不匹配的条件
        $results = $this->getRepository()->findByCampaignAndReferrer(
            'campaign-nonexistent',
            'user',
            'referrer-match'
        );
        $this->assertCount(0, $results);
    }

    public function testDeleteExpiredTokens(): void
    {
        // 清理已存在的数据
        self::getEntityManager()->createQuery('DELETE FROM ' . AttributionToken::class)->execute();

        $now = new \DateTimeImmutable();

        // 创建已过期的token
        $expiredToken1 = $this->createTestToken('expired-1', new \DateTimeImmutable('-2 hours'));
        $this->getRepository()->save($expiredToken1, true);

        $expiredToken2 = $this->createTestToken('expired-2', new \DateTimeImmutable('-1 hour'));
        $this->getRepository()->save($expiredToken2, true);

        // 创建还未过期的token
        $validToken = $this->createTestToken('valid-token', new \DateTimeImmutable('+1 hour'));
        $this->getRepository()->save($validToken, true);

        // 删除在30分钟前之前过期的token（即所有过期的token）
        $deletedCount = $this->getRepository()->deleteExpiredTokens(new \DateTimeImmutable('-30 minutes'));
        $this->assertSame(2, $deletedCount); // expired-1和expired-2都应该被删除

        // 验证结果
        $this->assertNull($this->getRepository()->findOneBy(['token' => 'expired-1']));
        $this->assertNull($this->getRepository()->findOneBy(['token' => 'expired-2']));
        $this->assertNotNull($this->getRepository()->findOneBy(['token' => 'valid-token'])); // 有效的不会被删除

        // 再次删除应该没有token被删除
        $deletedCount = $this->getRepository()->deleteExpiredTokens($now);
        $this->assertSame(0, $deletedCount); // 没有剩余的过期token

        // 验证只剩下有效token
        $this->assertNotNull($this->getRepository()->findOneBy(['token' => 'valid-token']));
    }

    public function testDeleteExpiredTokensWithNoExpiredTokens(): void
    {
        // 清理已存在的数据
        self::getEntityManager()->createQuery('DELETE FROM ' . AttributionToken::class)->execute();

        // 只创建有效token
        $validToken = $this->createTestToken('valid-only', new \DateTimeImmutable('+1 hour'));
        $this->getRepository()->save($validToken, true);

        $deletedCount = $this->getRepository()->deleteExpiredTokens(new \DateTimeImmutable());
        $this->assertSame(0, $deletedCount);

        // 验证token仍然存在
        $this->assertNotNull($this->getRepository()->findOneBy(['token' => 'valid-only']));
    }

    private function createTestToken(string $tokenValue = 'test-token-123', ?\DateTimeInterface $expireTime = null): AttributionToken
    {
        $uniqueId = uniqid();
        $token = new AttributionToken();
        $token->setToken($tokenValue);
        $token->setCampaignId('campaign-1');
        $token->setReferrerType('user');
        $token->setReferrerId('referrer-' . $uniqueId);
        $token->setExpireTime($expireTime ?? new \DateTimeImmutable('+1 hour'));
        $token->setCreateTime(new \DateTimeImmutable());

        return $token;
    }
}
