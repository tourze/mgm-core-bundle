<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\MgmCoreBundle\Entity\Ledger;
use Tourze\MgmCoreBundle\Entity\Reward;
use Tourze\MgmCoreBundle\Enum\Direction;

class LedgerFixtures extends Fixture implements DependentFixtureInterface
{
    public const LEDGER_REFERRER_GRANT = 'ledger_referrer_grant';
    public const LEDGER_REFEREE_GRANT = 'ledger_referee_grant';
    public const LEDGER_VIP_REFERRER_GRANT = 'ledger_vip_referrer_grant';
    public const LEDGER_VIP_REFEREE_GRANT = 'ledger_vip_referee_grant';
    public const LEDGER_EMPLOYEE_POINTS = 'ledger_employee_points';
    public const LEDGER_FRAUD_REVOKE = 'ledger_fraud_revoke';
    public const LEDGER_ADJUSTMENT_PLUS = 'ledger_adjustment_plus';
    public const LEDGER_ADJUSTMENT_MINUS = 'ledger_adjustment_minus';

    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable();

        $referrerGrantedReward = $this->getReference(RewardFixtures::REWARD_REFERRER_GRANTED, Reward::class);
        $refereeGrantedReward = $this->getReference(RewardFixtures::REWARD_REFEREE_GRANTED, Reward::class);
        $vipReferrerReward = $this->getReference(RewardFixtures::REWARD_VIP_REFERRER_GRANTED, Reward::class);
        $vipRefereeReward = $this->getReference(RewardFixtures::REWARD_VIP_REFEREE_GRANTED, Reward::class);
        $employeePointsReward = $this->getReference(RewardFixtures::REWARD_EMPLOYEE_POINTS, Reward::class);
        $cancelledReward = $this->getReference(RewardFixtures::REWARD_CANCELLED_FRAUD, Reward::class);

        // 为已发放的推荐人现金奖励创建账本记录
        $referrerGrantLedger = new Ledger();
        $referrerGrantLedger->setId('ledger-00000000000001');
        $referrerGrantLedger->setRewardId($referrerGrantedReward->getId());
        $referrerGrantLedger->setDirection(Direction::PLUS);
        $referrerGrantLedger->setAmount('50.0000');
        $referrerGrantLedger->setCurrency('CNY');
        $referrerGrantLedger->setReason('推荐奖励发放 - 现金奖励');
        $referrerGrantLedger->setCreateTime($now->modify('-1 day'));

        $manager->persist($referrerGrantLedger);
        $this->addReference(self::LEDGER_REFERRER_GRANT, $referrerGrantLedger);

        // 为已发放的被推荐人现金奖励创建账本记录
        $refereeGrantLedger = new Ledger();
        $refereeGrantLedger->setId('ledger-00000000000002');
        $refereeGrantLedger->setRewardId($refereeGrantedReward->getId());
        $refereeGrantLedger->setDirection(Direction::PLUS);
        $refereeGrantLedger->setAmount('20.0000');
        $refereeGrantLedger->setCurrency('CNY');
        $refereeGrantLedger->setReason('新用户奖励发放 - 现金奖励');
        $refereeGrantLedger->setCreateTime($now->modify('-1 day'));

        $manager->persist($refereeGrantLedger);
        $this->addReference(self::LEDGER_REFEREE_GRANT, $refereeGrantLedger);

        // 为VIP推荐人奖励创建账本记录
        $vipReferrerLedger = new Ledger();
        $vipReferrerLedger->setId('ledger-00000000000003');
        $vipReferrerLedger->setRewardId($vipReferrerReward->getId());
        $vipReferrerLedger->setDirection(Direction::PLUS);
        $vipReferrerLedger->setAmount('100.0000');
        $vipReferrerLedger->setCurrency('CNY');
        $vipReferrerLedger->setReason('VIP推荐奖励发放 - 高额现金奖励');
        $vipReferrerLedger->setCreateTime($now->modify('-1 day'));

        $manager->persist($vipReferrerLedger);
        $this->addReference(self::LEDGER_VIP_REFERRER_GRANT, $vipReferrerLedger);

        // 为VIP被推荐人奖励创建账本记录
        $vipRefereeLedger = new Ledger();
        $vipRefereeLedger->setId('ledger-00000000000004');
        $vipRefereeLedger->setRewardId($vipRefereeReward->getId());
        $vipRefereeLedger->setDirection(Direction::PLUS);
        $vipRefereeLedger->setAmount('50.0000');
        $vipRefereeLedger->setCurrency('CNY');
        $vipRefereeLedger->setReason('VIP新用户奖励发放 - 高额现金奖励');
        $vipRefereeLedger->setCreateTime($now->modify('-1 day'));

        $manager->persist($vipRefereeLedger);
        $this->addReference(self::LEDGER_VIP_REFEREE_GRANT, $vipRefereeLedger);

        // 为员工积分奖励创建账本记录（注意：积分不使用货币单位）
        $employeePointsLedger = new Ledger();
        $employeePointsLedger->setId('ledger-00000000000005');
        $employeePointsLedger->setRewardId($employeePointsReward->getId());
        $employeePointsLedger->setDirection(Direction::PLUS);
        $employeePointsLedger->setAmount('1000.0000');
        $employeePointsLedger->setCurrency('PTS'); // Points作为特殊货币单位
        $employeePointsLedger->setReason('员工推荐积分奖励发放');
        $employeePointsLedger->setCreateTime($now->modify('-10 days'));

        $manager->persist($employeePointsLedger);
        $this->addReference(self::LEDGER_EMPLOYEE_POINTS, $employeePointsLedger);

        // 为欺诈撤销奖励创建负向账本记录
        $fraudRevokeLedger = new Ledger();
        $fraudRevokeLedger->setId('ledger-00000000000006');
        $fraudRevokeLedger->setRewardId($cancelledReward->getId());
        $fraudRevokeLedger->setDirection(Direction::MINUS);
        $fraudRevokeLedger->setAmount('50.0000');
        $fraudRevokeLedger->setCurrency('CNY');
        $fraudRevokeLedger->setReason('欺诈检测 - 撤销推荐奖励');
        $fraudRevokeLedger->setCreateTime($now->modify('-8 days'));

        $manager->persist($fraudRevokeLedger);
        $this->addReference(self::LEDGER_FRAUD_REVOKE, $fraudRevokeLedger);

        // 创建一个正向调整记录
        $adjustmentPlusLedger = new Ledger();
        $adjustmentPlusLedger->setId('ledger-00000000000007');
        $adjustmentPlusLedger->setRewardId($referrerGrantedReward->getId());
        $adjustmentPlusLedger->setDirection(Direction::PLUS);
        $adjustmentPlusLedger->setAmount('5.0000');
        $adjustmentPlusLedger->setCurrency('CNY');
        $adjustmentPlusLedger->setReason('手动调整 - 补发奖励差额');
        $adjustmentPlusLedger->setCreateTime($now->modify('-12 hours'));

        $manager->persist($adjustmentPlusLedger);
        $this->addReference(self::LEDGER_ADJUSTMENT_PLUS, $adjustmentPlusLedger);

        // 创建一个负向调整记录
        $adjustmentMinusLedger = new Ledger();
        $adjustmentMinusLedger->setId('ledger-00000000000008');
        $adjustmentMinusLedger->setRewardId($vipReferrerReward->getId());
        $adjustmentMinusLedger->setDirection(Direction::MINUS);
        $adjustmentMinusLedger->setAmount('10.0000');
        $adjustmentMinusLedger->setCurrency('CNY');
        $adjustmentMinusLedger->setReason('手动调整 - 扣减重复发放金额');
        $adjustmentMinusLedger->setCreateTime($now->modify('-6 hours'));

        $manager->persist($adjustmentMinusLedger);
        $this->addReference(self::LEDGER_ADJUSTMENT_MINUS, $adjustmentMinusLedger);

        // 为VIP奖励创建额外的手续费扣减记录
        $feeDeductionLedger = new Ledger();
        $feeDeductionLedger->setId('ledger-00000000000009');
        $feeDeductionLedger->setRewardId($vipReferrerReward->getId());
        $feeDeductionLedger->setDirection(Direction::MINUS);
        $feeDeductionLedger->setAmount('2.5000');
        $feeDeductionLedger->setCurrency('CNY');
        $feeDeductionLedger->setReason('银行转账手续费扣减');
        $feeDeductionLedger->setCreateTime($now->modify('-1 day'));

        $manager->persist($feeDeductionLedger);

        // 为员工积分奖励创建奖励倍数记录
        $bonusMultiplierLedger = new Ledger();
        $bonusMultiplierLedger->setId('ledger-00000000000010');
        $bonusMultiplierLedger->setRewardId($employeePointsReward->getId());
        $bonusMultiplierLedger->setDirection(Direction::PLUS);
        $bonusMultiplierLedger->setAmount('500.0000');
        $bonusMultiplierLedger->setCurrency('PTS');
        $bonusMultiplierLedger->setReason('员工推荐季度奖励倍数 - 50%额外积分');
        $bonusMultiplierLedger->setCreateTime($now->modify('-9 days'));

        $manager->persist($bonusMultiplierLedger);

        $manager->flush();
    }

    /**
     * @return array<class-string<Fixture>>
     */
    public function getDependencies(): array
    {
        return [
            RewardFixtures::class,
        ];
    }
}
