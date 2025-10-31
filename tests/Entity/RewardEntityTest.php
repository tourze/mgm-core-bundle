<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\MgmCoreBundle\Entity\Reward;
use Tourze\MgmCoreBundle\Enum\Beneficiary;
use Tourze\MgmCoreBundle\Enum\RewardState;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * 测试Reward实体的新增方法
 * @internal
 */
#[CoversClass(Reward::class)]
class RewardEntityTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new Reward();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'id_property' => ['id', 'test-reward-123'];
        yield 'referralId_property' => ['referralId', 'test-referral-456'];
        yield 'beneficiary_property' => ['beneficiary', Beneficiary::REFERRER];
        yield 'beneficiaryType_property' => ['beneficiaryType', 'user'];
        yield 'beneficiaryId_property' => ['beneficiaryId', 'user-789'];
        yield 'type_property' => ['type', 'points'];
        yield 'state_property' => ['state', RewardState::PENDING];
        yield 'specJson_property' => ['specJson', ['amount' => 100]];
        yield 'idemKey_property' => ['idemKey', 'test-idem-key-123'];
        yield 'createTime_property' => ['createTime', new \DateTimeImmutable()];
    }

    public function testSetBeneficiaryTypeMethodExists(): void
    {
        $reward = new Reward();

        // 测试setBeneficiaryType方法存在并且可以正常调用
        $reward->setBeneficiaryType('user');
        $this->assertTrue(true); // 方法调用成功

        // 测试getBeneficiaryType方法返回正确的值
        $this->assertEquals('user', $reward->getBeneficiaryType());
    }

    public function testSetBeneficiaryIdMethodExists(): void
    {
        $reward = new Reward();

        // 测试setBeneficiaryId方法存在并且可以正常调用
        $reward->setBeneficiaryId('user-123');
        $this->assertTrue(true); // 方法调用成功

        // 测试getBeneficiaryId方法返回正确的值
        $this->assertEquals('user-123', $reward->getBeneficiaryId());
    }

    public function testRewardEntityWithAllRequiredFields(): void
    {
        $reward = new Reward();

        // 设置所有必填字段
        $reward->setId('test-reward-001');
        $reward->setReferralId('test-referral-001');
        $reward->setBeneficiary(Beneficiary::REFERRER);
        $reward->setBeneficiaryType('user');
        $reward->setBeneficiaryId('user-123');
        $reward->setType('points');
        $reward->setState(RewardState::PENDING);
        $reward->setSpecJson(['amount' => 100]);
        $reward->setIdemKey('test-idem-key-001');
        $reward->setCreateTime(new \DateTimeImmutable());

        // 验证所有getter方法返回正确的值
        $this->assertEquals('test-reward-001', $reward->getId());
        $this->assertEquals('test-referral-001', $reward->getReferralId());
        $this->assertEquals(Beneficiary::REFERRER, $reward->getBeneficiary());
        $this->assertEquals('user', $reward->getBeneficiaryType());
        $this->assertEquals('user-123', $reward->getBeneficiaryId());
        $this->assertEquals('points', $reward->getType());
        $this->assertEquals(RewardState::PENDING, $reward->getState());
        $this->assertEquals(['amount' => 100], $reward->getSpecJson());
        $this->assertEquals('test-idem-key-001', $reward->getIdemKey());
        $this->assertInstanceOf(\DateTimeImmutable::class, $reward->getCreateTime());
    }
}
