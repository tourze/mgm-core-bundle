<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\MgmCoreBundle\Entity\AttributionToken;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(AttributionToken::class)]
class AttributionTokenTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new AttributionToken();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        $now = new \DateTimeImmutable();
        yield 'token_property' => ['token', 'test-token-123'];
        yield 'campaignId_property' => ['campaignId', 'campaign-456'];
        yield 'referrerType_property' => ['referrerType', 'user'];
        yield 'referrerId_property' => ['referrerId', 'user-789'];
        yield 'expireTime_property' => ['expireTime', $now];
        yield 'createTime_property' => ['createTime', $now];
    }
    // extra behavior tests

    public function testToString(): void
    {
        $token = new AttributionToken();
        $token->setToken('test-token-123');

        $this->assertSame('test-token-123', (string) $token);
    }

    public function testDateTimeInterface(): void
    {
        $token = new AttributionToken();
        $dateTime = new \DateTimeImmutable();
        $dateTimeImmutable = new \DateTimeImmutable();

        $token->setExpireTime($dateTime);
        $this->assertInstanceOf(\DateTimeInterface::class, $token->getExpireTime());

        $token->setCreateTime($dateTimeImmutable);
        $this->assertInstanceOf(\DateTimeInterface::class, $token->getCreateTime());
    }

    public function testFluentInterface(): void
    {
        $token = new AttributionToken();
        $now = new \DateTimeImmutable();

        $token->setToken('test');
        $token->setCampaignId('campaign');
        $token->setReferrerType('user');
        $token->setReferrerId('123');
        $token->setExpireTime($now);
        $token->setCreateTime($now);

        // Since setters return void, we test that all properties are set correctly
        $this->assertSame('test', $token->getToken());
        $this->assertSame('campaign', $token->getCampaignId());
        $this->assertSame('user', $token->getReferrerType());
        $this->assertSame('123', $token->getReferrerId());
        $this->assertSame($now, $token->getExpireTime());
        $this->assertSame($now, $token->getCreateTime());
    }

    public function testStringableInterface(): void
    {
        $token = new AttributionToken();
        $this->assertInstanceOf(\Stringable::class, $token);
    }
}
