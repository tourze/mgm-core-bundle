<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\MgmCoreBundle\Entity\IdempotencyKey;

class IdempotencyKeyFixtures extends Fixture
{
    public const IDEM_KEY_REWARD_CREATION = 'idem_key_reward_creation';
    public const IDEM_KEY_QUALIFICATION_PROCESS = 'idem_key_qualification_process';
    public const IDEM_KEY_PAYMENT_PROCESSING = 'idem_key_payment_processing';
    public const IDEM_KEY_REFERRAL_CREATION = 'idem_key_referral_creation';
    public const IDEM_KEY_FRAUD_CHECK = 'idem_key_fraud_check';
    public const IDEM_KEY_VIP_PROCESSING = 'idem_key_vip_processing';
    public const IDEM_KEY_BATCH_PROCESSING = 'idem_key_batch_processing';

    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable();

        // 奖励创建幂等性键
        $rewardCreationKey = new IdempotencyKey();
        $rewardCreationKey->setKey('reward_creation_ref_qualified_003_' . hash('sha256', 'reward_creation_qualified'));
        $rewardCreationKey->setScope('reward_creation');
        $rewardCreationKey->setResultJson([
            'operation' => 'create_rewards',
            'referral_id' => 'ref_qualified_003',
            'rewards_created' => [
                'referrer' => 'reward_referrer_pending_001',
                'referee' => 'reward_referee_pending_002',
            ],
            'status' => 'success',
            'timestamp' => $now->modify('-1 day')->format('Y-m-d H:i:s'),
        ]);
        $rewardCreationKey->setCreateTime($now->modify('-1 day'));

        $manager->persist($rewardCreationKey);
        $this->addReference(self::IDEM_KEY_REWARD_CREATION, $rewardCreationKey);

        // 资格审核幂等性键
        $qualificationProcessKey = new IdempotencyKey();
        $qualificationProcessKey->setKey('qualification_process_ref_rewarded_004_' . hash('sha256', 'qualification_check'));
        $qualificationProcessKey->setScope('qualification_process');
        $qualificationProcessKey->setResultJson([
            'operation' => 'process_qualification',
            'referral_id' => 'ref_rewarded_004',
            'qualification_id' => 'qual_rewarded_002',
            'decision' => 'qualified',
            'evidence_verified' => true,
            'status' => 'completed',
            'timestamp' => $now->modify('-3 days')->format('Y-m-d H:i:s'),
        ]);
        $qualificationProcessKey->setCreateTime($now->modify('-3 days'));

        $manager->persist($qualificationProcessKey);
        $this->addReference(self::IDEM_KEY_QUALIFICATION_PROCESS, $qualificationProcessKey);

        // 支付处理幂等性键
        $paymentProcessingKey = new IdempotencyKey();
        $paymentProcessingKey->setKey('payment_processing_ext_payment_20250831_001');
        $paymentProcessingKey->setScope('payment_processing');
        $paymentProcessingKey->setResultJson([
            'operation' => 'process_payment',
            'external_payment_id' => 'ext_payment_20250831_001',
            'reward_id' => 'reward_referrer_granted_003',
            'amount' => 50.00,
            'currency' => 'CNY',
            'payment_method' => 'bank_transfer',
            'payment_status' => 'success',
            'ledger_entry' => 'ledger_referrer_grant_001',
            'timestamp' => $now->modify('-1 day')->format('Y-m-d H:i:s'),
        ]);
        $paymentProcessingKey->setCreateTime($now->modify('-1 day'));

        $manager->persist($paymentProcessingKey);
        $this->addReference(self::IDEM_KEY_PAYMENT_PROCESSING, $paymentProcessingKey);

        // 推荐关系创建幂等性键
        $referralCreationKey = new IdempotencyKey();
        $referralCreationKey->setKey('referral_creation_user123_user501_' . hash('sha256', 'referral_spring_campaign'));
        $referralCreationKey->setScope('referral_creation');
        $referralCreationKey->setResultJson([
            'operation' => 'create_referral',
            'referrer' => 'user_123',
            'referee' => 'user_501',
            'campaign_id' => 'spring-campaign-2025',
            'referral_id' => 'ref_created_001',
            'token_used' => true,
            'source' => 'web_share',
            'status' => 'created',
            'timestamp' => $now->format('Y-m-d H:i:s'),
        ]);
        $referralCreationKey->setCreateTime($now);

        $manager->persist($referralCreationKey);
        $this->addReference(self::IDEM_KEY_REFERRAL_CREATION, $referralCreationKey);

        // 欺诈检测幂等性键
        $fraudCheckKey = new IdempotencyKey();
        $fraudCheckKey->setKey('fraud_check_ref_revoked_005_' . hash('sha256', 'fraud_detection_ml'));
        $fraudCheckKey->setScope('fraud_detection');
        $fraudCheckKey->setResultJson([
            'operation' => 'fraud_check',
            'referral_id' => 'ref_revoked_005',
            'ml_score' => 95,
            'risk_indicators' => [
                'same_ip_multiple_accounts',
                'fake_order_pattern',
                'payment_source_suspicious',
            ],
            'decision' => 'fraud_detected',
            'qualification_id' => 'qual_fraud_rejected_003',
            'reward_cancelled' => true,
            'timestamp' => $now->modify('-12 days')->format('Y-m-d H:i:s'),
        ]);
        $fraudCheckKey->setCreateTime($now->modify('-12 days'));

        $manager->persist($fraudCheckKey);
        $this->addReference(self::IDEM_KEY_FRAUD_CHECK, $fraudCheckKey);

        // VIP处理幂等性键
        $vipProcessingKey = new IdempotencyKey();
        $vipProcessingKey->setKey('vip_processing_ref_vip_qualified_006_' . hash('sha256', 'vip_manual_review'));
        $vipProcessingKey->setScope('vip_processing');
        $vipProcessingKey->setResultJson([
            'operation' => 'vip_review',
            'referral_id' => 'ref_vip_qualified_006',
            'campaign_id' => 'vip-campaign-2025',
            'order_amount' => 1200.00,
            'user_level' => 'gold',
            'manual_review' => true,
            'reviewer_id' => 'admin_001',
            'qualification_id' => 'qual_vip_qualified_004',
            'rewards_created' => [
                'referrer' => 'reward_vip_referrer_005',
                'referee' => 'reward_vip_referee_006',
            ],
            'status' => 'approved',
            'timestamp' => $now->modify('-2 days')->format('Y-m-d H:i:s'),
        ]);
        $vipProcessingKey->setCreateTime($now->modify('-2 days'));

        $manager->persist($vipProcessingKey);
        $this->addReference(self::IDEM_KEY_VIP_PROCESSING, $vipProcessingKey);

        // 批处理幂等性键
        $batchProcessingKey = new IdempotencyKey();
        $batchProcessingKey->setKey('batch_processing_daily_rewards_' . $now->modify('-1 day')->format('Ymd'));
        $batchProcessingKey->setScope('batch_processing');
        $batchProcessingKey->setResultJson([
            'operation' => 'daily_reward_batch',
            'date' => $now->modify('-1 day')->format('Y-m-d'),
            'processed_referrals' => [
                'ref_qualified_003',
                'ref_rewarded_004',
                'ref_vip_qualified_006',
                'ref_first_touch_008',
            ],
            'rewards_granted' => 8,
            'total_amount' => [
                'CNY' => 295.00,
                'PTS' => 1500,
            ],
            'ledger_entries' => 10,
            'failed_operations' => 0,
            'status' => 'completed',
            'timestamp' => $now->modify('-1 day')->modify('+2 hours')->format('Y-m-d H:i:s'),
        ]);
        $batchProcessingKey->setCreateTime($now->modify('-1 day')->modify('+2 hours'));

        $manager->persist($batchProcessingKey);
        $this->addReference(self::IDEM_KEY_BATCH_PROCESSING, $batchProcessingKey);

        // 创建一个员工推荐专用的幂等性键
        $employeeProcessingKey = new IdempotencyKey();
        $employeeProcessingKey->setKey('employee_processing_ref_employee_009_' . hash('sha256', 'employee_internal'));
        $employeeProcessingKey->setScope('employee_processing');
        $employeeProcessingKey->setResultJson([
            'operation' => 'employee_referral',
            'referral_id' => 'ref_employee_009',
            'employee_id' => 'employee_404',
            'department' => 'marketing',
            'approval_manager' => 'manager_hr_001',
            'qualification_id' => 'qual_employee_007',
            'points_reward' => 'reward_employee_points_009',
            'bonus_multiplier' => 1.5,
            'internal_program' => true,
            'status' => 'approved_and_rewarded',
            'timestamp' => $now->modify('-15 days')->format('Y-m-d H:i:s'),
        ]);
        $employeeProcessingKey->setCreateTime($now->modify('-15 days'));

        $manager->persist($employeeProcessingKey);

        // 创建一个首次归因处理的幂等性键
        $firstTouchProcessingKey = new IdempotencyKey();
        $firstTouchProcessingKey->setKey('first_touch_processing_ref_first_touch_008_' . hash('sha256', 'first_attribution'));
        $firstTouchProcessingKey->setScope('first_touch_processing');
        $firstTouchProcessingKey->setResultJson([
            'operation' => 'first_touch_attribution',
            'referral_id' => 'ref_first_touch_008',
            'campaign_id' => 'first-touch-campaign-2025',
            'attribution_method' => 'first_touch',
            'user_first_order' => true,
            'qualification_id' => 'qual_first_touch_006',
            'rewards_created' => [
                'referrer' => 'reward_first_referrer_007',
                'referee' => 'reward_first_referee_008',
            ],
            'coupon_issued' => 'FIRST25',
            'status' => 'processed',
            'timestamp' => $now->modify('-1 day')->format('Y-m-d H:i:s'),
        ]);
        $firstTouchProcessingKey->setCreateTime($now->modify('-1 day'));

        $manager->persist($firstTouchProcessingKey);

        $manager->flush();
    }
}
