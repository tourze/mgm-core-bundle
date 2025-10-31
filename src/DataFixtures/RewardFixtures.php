<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\MgmCoreBundle\Entity\Referral;
use Tourze\MgmCoreBundle\Entity\Reward;
use Tourze\MgmCoreBundle\Enum\Beneficiary;
use Tourze\MgmCoreBundle\Enum\RewardState;

class RewardFixtures extends Fixture implements DependentFixtureInterface
{
    public const REWARD_REFERRER_PENDING = 'reward_referrer_pending';
    public const REWARD_REFEREE_PENDING = 'reward_referee_pending';
    public const REWARD_REFERRER_GRANTED = 'reward_referrer_granted';
    public const REWARD_REFEREE_GRANTED = 'reward_referee_granted';
    public const REWARD_VIP_REFERRER_GRANTED = 'reward_vip_referrer_granted';
    public const REWARD_VIP_REFEREE_GRANTED = 'reward_vip_referee_granted';
    public const REWARD_FIRST_TOUCH_REFERRER = 'reward_first_touch_referrer';
    public const REWARD_FIRST_TOUCH_REFEREE = 'reward_first_touch_referee';
    public const REWARD_EMPLOYEE_POINTS = 'reward_employee_points';
    public const REWARD_CANCELLED_FRAUD = 'reward_cancelled_fraud';

    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable();

        $qualifiedReferral = $this->getReference(ReferralFixtures::REFERRAL_QUALIFIED, Referral::class);
        $rewardedReferral = $this->getReference(ReferralFixtures::REFERRAL_REWARDED, Referral::class);
        $vipQualifiedReferral = $this->getReference(ReferralFixtures::REFERRAL_VIP_QUALIFIED, Referral::class);
        $firstTouchReferral = $this->getReference(ReferralFixtures::REFERRAL_FIRST_TOUCH, Referral::class);
        $employeeReferral = $this->getReference(ReferralFixtures::REFERRAL_EMPLOYEE, Referral::class);
        $revokedReferral = $this->getReference(ReferralFixtures::REFERRAL_REVOKED, Referral::class);

        // 为通过审核的推荐创建待发放的推荐人奖励
        $referrerPendingReward = new Reward();
        $referrerPendingReward->setId('rew-00000000000001');
        $referrerPendingReward->setReferralId($qualifiedReferral->getId());
        $referrerPendingReward->setBeneficiary(Beneficiary::REFERRER);
        $referrerPendingReward->setBeneficiaryType($qualifiedReferral->getReferrerType());
        $referrerPendingReward->setBeneficiaryId($qualifiedReferral->getReferrerId());
        $referrerPendingReward->setType('cash');
        $referrerPendingReward->setSpecJson([
            'amount' => 50.0,
            'currency' => 'CNY',
            'description' => '推荐奖励 - 现金',
            'payment_method' => 'wallet',
        ]);
        $referrerPendingReward->setState(RewardState::PENDING);
        $referrerPendingReward->setExternalIssueId(null);
        $referrerPendingReward->setIdemKey('idem_referrer_' . $qualifiedReferral->getId() . '_001');
        $referrerPendingReward->setCreateTime($now->modify('-1 day'));
        $referrerPendingReward->setGrantTime(null);
        $referrerPendingReward->setRevokeTime(null);

        $manager->persist($referrerPendingReward);
        $this->addReference(self::REWARD_REFERRER_PENDING, $referrerPendingReward);

        // 为通过审核的推荐创建待发放的被推荐人奖励
        $refereePendingReward = new Reward();
        $refereePendingReward->setId('rew-00000000000002');
        $refereePendingReward->setReferralId($qualifiedReferral->getId());
        $refereePendingReward->setBeneficiary(Beneficiary::REFEREE);
        $refereePendingReward->setBeneficiaryType($qualifiedReferral->getRefereeType());
        $refereePendingReward->setBeneficiaryId($qualifiedReferral->getRefereeId());
        $refereePendingReward->setType('cash');
        $refereePendingReward->setSpecJson([
            'amount' => 20.0,
            'currency' => 'CNY',
            'description' => '新用户奖励 - 现金',
            'payment_method' => 'wallet',
        ]);
        $refereePendingReward->setState(RewardState::PENDING);
        $refereePendingReward->setExternalIssueId(null);
        $refereePendingReward->setIdemKey('idem_referee_' . $qualifiedReferral->getId() . '_002');
        $refereePendingReward->setCreateTime($now->modify('-1 day'));
        $refereePendingReward->setGrantTime(null);
        $refereePendingReward->setRevokeTime(null);

        $manager->persist($refereePendingReward);
        $this->addReference(self::REWARD_REFEREE_PENDING, $refereePendingReward);

        // 为已发放奖励的推荐创建已发放的推荐人奖励
        $referrerGrantedReward = new Reward();
        $referrerGrantedReward->setId('rew-00000000000003');
        $referrerGrantedReward->setReferralId($rewardedReferral->getId());
        $referrerGrantedReward->setBeneficiary(Beneficiary::REFERRER);
        $referrerGrantedReward->setBeneficiaryType($rewardedReferral->getReferrerType());
        $referrerGrantedReward->setBeneficiaryId($rewardedReferral->getReferrerId());
        $referrerGrantedReward->setType('cash');
        $referrerGrantedReward->setSpecJson([
            'amount' => 50.0,
            'currency' => 'CNY',
            'description' => '推荐奖励 - 现金',
            'payment_method' => 'bank_transfer',
        ]);
        $referrerGrantedReward->setState(RewardState::GRANTED);
        $referrerGrantedReward->setExternalIssueId('ext_payment_20250831_001');
        $referrerGrantedReward->setIdemKey('idem_referrer_' . $rewardedReferral->getId() . '_003');
        $referrerGrantedReward->setCreateTime($now->modify('-3 days'));
        $referrerGrantedReward->setGrantTime($now->modify('-1 day'));
        $referrerGrantedReward->setRevokeTime(null);

        $manager->persist($referrerGrantedReward);
        $this->addReference(self::REWARD_REFERRER_GRANTED, $referrerGrantedReward);

        // 为已发放奖励的推荐创建已发放的被推荐人奖励
        $refereeGrantedReward = new Reward();
        $refereeGrantedReward->setId('rew-00000000000004');
        $refereeGrantedReward->setReferralId($rewardedReferral->getId());
        $refereeGrantedReward->setBeneficiary(Beneficiary::REFEREE);
        $refereeGrantedReward->setBeneficiaryType($rewardedReferral->getRefereeType());
        $refereeGrantedReward->setBeneficiaryId($rewardedReferral->getRefereeId());
        $refereeGrantedReward->setType('cash');
        $refereeGrantedReward->setSpecJson([
            'amount' => 20.0,
            'currency' => 'CNY',
            'description' => '新用户奖励 - 现金',
            'payment_method' => 'wallet',
        ]);
        $refereeGrantedReward->setState(RewardState::GRANTED);
        $refereeGrantedReward->setExternalIssueId('ext_payment_20250831_002');
        $refereeGrantedReward->setIdemKey('idem_referee_' . $rewardedReferral->getId() . '_004');
        $refereeGrantedReward->setCreateTime($now->modify('-3 days'));
        $refereeGrantedReward->setGrantTime($now->modify('-1 day'));
        $refereeGrantedReward->setRevokeTime(null);

        $manager->persist($refereeGrantedReward);
        $this->addReference(self::REWARD_REFEREE_GRANTED, $refereeGrantedReward);

        // 为VIP活动创建已发放的推荐人奖励
        $vipReferrerReward = new Reward();
        $vipReferrerReward->setId('rew-00000000000005');
        $vipReferrerReward->setReferralId($vipQualifiedReferral->getId());
        $vipReferrerReward->setBeneficiary(Beneficiary::REFERRER);
        $vipReferrerReward->setBeneficiaryType($vipQualifiedReferral->getReferrerType());
        $vipReferrerReward->setBeneficiaryId($vipQualifiedReferral->getReferrerId());
        $vipReferrerReward->setType('cash');
        $vipReferrerReward->setSpecJson([
            'amount' => 100.0,
            'currency' => 'CNY',
            'description' => 'VIP推荐奖励 - 现金',
            'payment_method' => 'bank_transfer',
            'bonus_rate' => 2.0,
        ]);
        $vipReferrerReward->setState(RewardState::GRANTED);
        $vipReferrerReward->setExternalIssueId('ext_vip_payment_20250830_001');
        $vipReferrerReward->setIdemKey('idem_vip_referrer_' . $vipQualifiedReferral->getId() . '_005');
        $vipReferrerReward->setCreateTime($now->modify('-2 days'));
        $vipReferrerReward->setGrantTime($now->modify('-1 day'));
        $vipReferrerReward->setRevokeTime(null);

        $manager->persist($vipReferrerReward);
        $this->addReference(self::REWARD_VIP_REFERRER_GRANTED, $vipReferrerReward);

        // 为VIP活动创建已发放的被推荐人奖励
        $vipRefereeReward = new Reward();
        $vipRefereeReward->setId('rew-00000000000006');
        $vipRefereeReward->setReferralId($vipQualifiedReferral->getId());
        $vipRefereeReward->setBeneficiary(Beneficiary::REFEREE);
        $vipRefereeReward->setBeneficiaryType($vipQualifiedReferral->getRefereeType());
        $vipRefereeReward->setBeneficiaryId($vipQualifiedReferral->getRefereeId());
        $vipRefereeReward->setType('cash');
        $vipRefereeReward->setSpecJson([
            'amount' => 50.0,
            'currency' => 'CNY',
            'description' => 'VIP新用户奖励 - 现金',
            'payment_method' => 'wallet',
            'bonus_rate' => 2.5,
        ]);
        $vipRefereeReward->setState(RewardState::GRANTED);
        $vipRefereeReward->setExternalIssueId('ext_vip_payment_20250830_002');
        $vipRefereeReward->setIdemKey('idem_vip_referee_' . $vipQualifiedReferral->getId() . '_006');
        $vipRefereeReward->setCreateTime($now->modify('-2 days'));
        $vipRefereeReward->setGrantTime($now->modify('-1 day'));
        $vipRefereeReward->setRevokeTime(null);

        $manager->persist($vipRefereeReward);
        $this->addReference(self::REWARD_VIP_REFEREE_GRANTED, $vipRefereeReward);

        // 为首次归因活动创建推荐人奖励
        $firstTouchReferrerReward = new Reward();
        $firstTouchReferrerReward->setId('rew-00000000000007');
        $firstTouchReferrerReward->setReferralId($firstTouchReferral->getId());
        $firstTouchReferrerReward->setBeneficiary(Beneficiary::REFERRER);
        $firstTouchReferrerReward->setBeneficiaryType($firstTouchReferral->getReferrerType());
        $firstTouchReferrerReward->setBeneficiaryId($firstTouchReferral->getReferrerId());
        $firstTouchReferrerReward->setType('cash');
        $firstTouchReferrerReward->setSpecJson([
            'amount' => 25.0,
            'currency' => 'CNY',
            'description' => '首次归因推荐奖励 - 现金',
            'payment_method' => 'wallet',
        ]);
        $firstTouchReferrerReward->setState(RewardState::PENDING);
        $firstTouchReferrerReward->setExternalIssueId(null);
        $firstTouchReferrerReward->setIdemKey('idem_first_referrer_' . $firstTouchReferral->getId() . '_007');
        $firstTouchReferrerReward->setCreateTime($now->modify('-1 day'));
        $firstTouchReferrerReward->setGrantTime(null);
        $firstTouchReferrerReward->setRevokeTime(null);

        $manager->persist($firstTouchReferrerReward);
        $this->addReference(self::REWARD_FIRST_TOUCH_REFERRER, $firstTouchReferrerReward);

        // 为首次归因活动创建被推荐人优惠券奖励
        $firstTouchRefereeReward = new Reward();
        $firstTouchRefereeReward->setId('rew-00000000000008');
        $firstTouchRefereeReward->setReferralId($firstTouchReferral->getId());
        $firstTouchRefereeReward->setBeneficiary(Beneficiary::REFEREE);
        $firstTouchRefereeReward->setBeneficiaryType($firstTouchReferral->getRefereeType());
        $firstTouchRefereeReward->setBeneficiaryId($firstTouchReferral->getRefereeId());
        $firstTouchRefereeReward->setType('coupon');
        $firstTouchRefereeReward->setSpecJson([
            'coupon_code' => 'FIRST25',
            'discount_amount' => 25.0,
            'currency' => 'CNY',
            'description' => '首次注册优惠券',
            'expire_days' => 30,
        ]);
        $firstTouchRefereeReward->setState(RewardState::PENDING);
        $firstTouchRefereeReward->setExternalIssueId(null);
        $firstTouchRefereeReward->setIdemKey('idem_first_referee_' . $firstTouchReferral->getId() . '_008');
        $firstTouchRefereeReward->setCreateTime($now->modify('-1 day'));
        $firstTouchRefereeReward->setGrantTime(null);
        $firstTouchRefereeReward->setRevokeTime(null);

        $manager->persist($firstTouchRefereeReward);
        $this->addReference(self::REWARD_FIRST_TOUCH_REFEREE, $firstTouchRefereeReward);

        // 为员工推荐创建积分奖励
        $employeePointsReward = new Reward();
        $employeePointsReward->setId('rew-00000000000009');
        $employeePointsReward->setReferralId($employeeReferral->getId());
        $employeePointsReward->setBeneficiary(Beneficiary::REFERRER);
        $employeePointsReward->setBeneficiaryType($employeeReferral->getReferrerType());
        $employeePointsReward->setBeneficiaryId($employeeReferral->getReferrerId());
        $employeePointsReward->setType('points');
        $employeePointsReward->setSpecJson([
            'points' => 1000,
            'description' => '员工推荐积分奖励',
            'point_type' => 'internal_credits',
        ]);
        $employeePointsReward->setState(RewardState::GRANTED);
        $employeePointsReward->setExternalIssueId('ext_points_20250812_001');
        $employeePointsReward->setIdemKey('idem_employee_' . $employeeReferral->getId() . '_009');
        $employeePointsReward->setCreateTime($now->modify('-15 days'));
        $employeePointsReward->setGrantTime($now->modify('-10 days'));
        $employeePointsReward->setRevokeTime(null);

        $manager->persist($employeePointsReward);
        $this->addReference(self::REWARD_EMPLOYEE_POINTS, $employeePointsReward);

        // 为撤销的推荐创建已取消的奖励
        $cancelledReward = new Reward();
        $cancelledReward->setId('rew-00000000000010');
        $cancelledReward->setReferralId($revokedReferral->getId());
        $cancelledReward->setBeneficiary(Beneficiary::REFERRER);
        $cancelledReward->setBeneficiaryType($revokedReferral->getReferrerType());
        $cancelledReward->setBeneficiaryId($revokedReferral->getReferrerId());
        $cancelledReward->setType('cash');
        $cancelledReward->setSpecJson([
            'amount' => 50.0,
            'currency' => 'CNY',
            'description' => '推荐奖励 - 已取消（欺诈检测）',
            'payment_method' => 'wallet',
            'cancellation_reason' => 'fraud_detected',
        ]);
        $cancelledReward->setState(RewardState::CANCELLED);
        $cancelledReward->setExternalIssueId('ext_payment_cancelled_20250820_001');
        $cancelledReward->setIdemKey('idem_fraud_' . $revokedReferral->getId() . '_010');
        $cancelledReward->setCreateTime($now->modify('-12 days'));
        $cancelledReward->setGrantTime($now->modify('-10 days'));
        $cancelledReward->setRevokeTime($now->modify('-8 days'));

        $manager->persist($cancelledReward);
        $this->addReference(self::REWARD_CANCELLED_FRAUD, $cancelledReward);

        $manager->flush();
    }

    /**
     * @return array<class-string<Fixture>>
     */
    public function getDependencies(): array
    {
        return [
            ReferralFixtures::class,
        ];
    }
}
