<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\MgmCoreBundle\Entity\Qualification;
use Tourze\MgmCoreBundle\Entity\Referral;
use Tourze\MgmCoreBundle\Enum\Decision;

class QualificationFixtures extends Fixture implements DependentFixtureInterface
{
    public const QUALIFICATION_QUALIFIED = 'qualification_qualified';
    public const QUALIFICATION_REJECTED_FRAUD = 'qualification_rejected_fraud';
    public const QUALIFICATION_QUALIFIED_VIP = 'qualification_qualified_vip';
    public const QUALIFICATION_REJECTED_DUPLICATE = 'qualification_rejected_duplicate';
    public const QUALIFICATION_QUALIFIED_FIRST_TOUCH = 'qualification_qualified_first_touch';
    public const QUALIFICATION_QUALIFIED_EMPLOYEE = 'qualification_qualified_employee';

    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable();

        $qualifiedReferral = $this->getReference(ReferralFixtures::REFERRAL_QUALIFIED, Referral::class);
        $rewardedReferral = $this->getReference(ReferralFixtures::REFERRAL_REWARDED, Referral::class);
        $revokedReferral = $this->getReference(ReferralFixtures::REFERRAL_REVOKED, Referral::class);
        $vipQualifiedReferral = $this->getReference(ReferralFixtures::REFERRAL_VIP_QUALIFIED, Referral::class);
        $firstTouchReferral = $this->getReference(ReferralFixtures::REFERRAL_FIRST_TOUCH, Referral::class);
        $employeeReferral = $this->getReference(ReferralFixtures::REFERRAL_EMPLOYEE, Referral::class);

        // 为通过审核的推荐创建资格记录
        $qualifiedQualification = new Qualification();
        $qualifiedQualification->setId('qual-00000000000001');
        $qualifiedQualification->setReferralId($qualifiedReferral->getId());
        $qualifiedQualification->setDecision(Decision::QUALIFIED);
        $qualifiedQualification->setReason('用户完成首次订单，金额满足活动要求');
        $qualifiedQualification->setEvidenceJson([
            'order_id' => 'order_20250901_001',
            'order_amount' => 150.00,
            'order_currency' => 'CNY',
            'order_time' => $now->modify('-1 day')->format('Y-m-d H:i:s'),
            'user_level' => 'regular',
            'verification_method' => 'automatic',
        ]);
        $qualifiedQualification->setOccurTime($now->modify('-1 day'));
        $qualifiedQualification->setCreateTime($now->modify('-1 day'));

        $manager->persist($qualifiedQualification);
        $this->addReference(self::QUALIFICATION_QUALIFIED, $qualifiedQualification);

        // 为已发放奖励的推荐创建资格记录
        $rewardedQualification = new Qualification();
        $rewardedQualification->setId('qual-00000000000002');
        $rewardedQualification->setReferralId($rewardedReferral->getId());
        $rewardedQualification->setDecision(Decision::QUALIFIED);
        $rewardedQualification->setReason('推荐用户完成多笔订单，累计金额达标');
        $rewardedQualification->setEvidenceJson([
            'orders' => [
                ['id' => 'order_20250825_001', 'amount' => 80.00, 'currency' => 'CNY'],
                ['id' => 'order_20250827_002', 'amount' => 120.00, 'currency' => 'CNY'],
            ],
            'total_amount' => 200.00,
            'order_count' => 2,
            'verification_method' => 'automatic',
            'payment_verified' => true,
        ]);
        $rewardedQualification->setOccurTime($now->modify('-3 days'));
        $rewardedQualification->setCreateTime($now->modify('-3 days'));

        $manager->persist($rewardedQualification);

        // 为撤销的推荐创建欺诈拒绝记录
        $fraudRejection = new Qualification();
        $fraudRejection->setId('qual-00000000000003');
        $fraudRejection->setReferralId($revokedReferral->getId());
        $fraudRejection->setDecision(Decision::REJECTED);
        $fraudRejection->setReason('检测到可疑行为：虚假订单和刷单行为');
        $fraudRejection->setEvidenceJson([
            'fraud_indicators' => [
                'same_ip_multiple_accounts',
                'fake_order_pattern',
                'payment_source_suspicious',
            ],
            'risk_score' => 95,
            'detection_method' => 'ml_algorithm',
            'reviewer' => 'fraud_detection_system',
            'investigation_details' => '用户使用相同IP地址注册多个账号，创建虚假订单',
        ]);
        $fraudRejection->setOccurTime($now->modify('-12 days'));
        $fraudRejection->setCreateTime($now->modify('-12 days'));

        $manager->persist($fraudRejection);
        $this->addReference(self::QUALIFICATION_REJECTED_FRAUD, $fraudRejection);

        // 为VIP推荐创建资格记录
        $vipQualification = new Qualification();
        $vipQualification->setId('qual-00000000000004');
        $vipQualification->setReferralId($vipQualifiedReferral->getId());
        $vipQualification->setDecision(Decision::QUALIFIED);
        $vipQualification->setReason('VIP用户推荐成功，被推荐人达到VIP活动要求');
        $vipQualification->setEvidenceJson([
            'order_id' => 'order_vip_20250830_001',
            'order_amount' => 1200.00,
            'order_currency' => 'CNY',
            'user_level' => 'gold',
            'vip_requirements_met' => true,
            'verification_method' => 'manual_review',
            'reviewer_id' => 'admin_001',
        ]);
        $vipQualification->setOccurTime($now->modify('-2 days'));
        $vipQualification->setCreateTime($now->modify('-2 days'));

        $manager->persist($vipQualification);
        $this->addReference(self::QUALIFICATION_QUALIFIED_VIP, $vipQualification);

        // 创建重复推荐的拒绝记录
        $duplicateRejection = new Qualification();
        $duplicateRejection->setId('qual-00000000000005');
        $duplicateRejection->setReferralId($qualifiedReferral->getId());
        $duplicateRejection->setDecision(Decision::REJECTED);
        $duplicateRejection->setReason('检测到重复推荐，该用户已被其他人成功推荐');
        $duplicateRejection->setEvidenceJson([
            'existing_referral_id' => 'ref_existing_001',
            'existing_referrer' => 'user_999',
            'existing_date' => $now->modify('-10 days')->format('Y-m-d H:i:s'),
            'duplicate_detection_method' => 'user_id_check',
            'policy_reference' => 'referral_policy_v2.1',
        ]);
        $duplicateRejection->setOccurTime($now->modify('-6 days'));
        $duplicateRejection->setCreateTime($now->modify('-6 days'));

        $manager->persist($duplicateRejection);
        $this->addReference(self::QUALIFICATION_REJECTED_DUPLICATE, $duplicateRejection);

        // 为首次归因活动创建资格记录
        $firstTouchQualification = new Qualification();
        $firstTouchQualification->setId('qual-00000000000006');
        $firstTouchQualification->setReferralId($firstTouchReferral->getId());
        $firstTouchQualification->setDecision(Decision::QUALIFIED);
        $firstTouchQualification->setReason('新用户首次下单成功，符合首次归因活动要求');
        $firstTouchQualification->setEvidenceJson([
            'order_id' => 'order_first_20250828_001',
            'order_amount' => 89.99,
            'order_currency' => 'CNY',
            'is_first_order' => true,
            'user_registration_date' => $now->modify('-4 days')->format('Y-m-d H:i:s'),
            'attribution_method' => 'first_touch',
            'verification_method' => 'automatic',
        ]);
        $firstTouchQualification->setOccurTime($now->modify('-1 day'));
        $firstTouchQualification->setCreateTime($now->modify('-1 day'));

        $manager->persist($firstTouchQualification);
        $this->addReference(self::QUALIFICATION_QUALIFIED_FIRST_TOUCH, $firstTouchQualification);

        // 为员工推荐创建资格记录
        $employeeQualification = new Qualification();
        $employeeQualification->setId('qual-00000000000007');
        $employeeQualification->setReferralId($employeeReferral->getId());
        $employeeQualification->setDecision(Decision::QUALIFIED);
        $employeeQualification->setReason('员工推荐计划，被推荐人完成注册和首次购买');
        $employeeQualification->setEvidenceJson([
            'employee_id' => 'employee_404',
            'department' => 'marketing',
            'referee_order_id' => 'order_employee_20250812_001',
            'referee_order_amount' => 299.99,
            'internal_program' => true,
            'approval_manager' => 'manager_hr_001',
            'verification_method' => 'internal_review',
        ]);
        $employeeQualification->setOccurTime($now->modify('-15 days'));
        $employeeQualification->setCreateTime($now->modify('-15 days'));

        $manager->persist($employeeQualification);
        $this->addReference(self::QUALIFICATION_QUALIFIED_EMPLOYEE, $employeeQualification);

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
