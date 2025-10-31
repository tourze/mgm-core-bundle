<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\MgmCoreBundle\Entity\AttributionToken;
use Tourze\MgmCoreBundle\Entity\Campaign;
use Tourze\MgmCoreBundle\Entity\Referral;
use Tourze\MgmCoreBundle\Enum\ReferralState;

class ReferralFixtures extends Fixture implements DependentFixtureInterface
{
    public const REFERRAL_CREATED = 'referral_created';
    public const REFERRAL_ATTRIBUTED = 'referral_attributed';
    public const REFERRAL_QUALIFIED = 'referral_qualified';
    public const REFERRAL_REWARDED = 'referral_rewarded';
    public const REFERRAL_REVOKED = 'referral_revoked';
    public const REFERRAL_VIP_QUALIFIED = 'referral_vip_qualified';
    public const REFERRAL_MOBILE_APP = 'referral_mobile_app';
    public const REFERRAL_FIRST_TOUCH = 'referral_first_touch';
    public const REFERRAL_EMPLOYEE = 'referral_employee';

    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable();

        $activeCampaign = $this->getReference(CampaignFixtures::CAMPAIGN_ACTIVE, Campaign::class);
        $vipCampaign = $this->getReference(CampaignFixtures::CAMPAIGN_LIMITED_BUDGET, Campaign::class);
        $firstCampaign = $this->getReference(CampaignFixtures::CAMPAIGN_FIRST_ATTRIBUTION, Campaign::class);
        $internalCampaign = $this->getReference(CampaignFixtures::CAMPAIGN_NO_SELF_BLOCK, Campaign::class);

        $activeUserToken = $this->getReference(AttributionTokenFixtures::TOKEN_ACTIVE_USER, AttributionToken::class);
        $vipUserToken = $this->getReference(AttributionTokenFixtures::TOKEN_VIP_USER, AttributionToken::class);

        // 创建刚建立的推荐关系
        $createdReferral = new Referral();
        $createdReferral->setId('ref-00000000000001');
        $createdReferral->setCampaignId($activeCampaign->getId());
        $createdReferral->setReferrerType('user');
        $createdReferral->setReferrerId('user_123');
        $createdReferral->setRefereeType('user');
        $createdReferral->setRefereeId('user_501');
        $createdReferral->setToken($activeUserToken->getToken());
        $createdReferral->setSource('web_share');
        $createdReferral->setState(ReferralState::CREATED);
        $createdReferral->setCreateTime($now);
        $createdReferral->setQualifyTime(null);
        $createdReferral->setRewardTime(null);

        $manager->persist($createdReferral);
        $this->addReference(self::REFERRAL_CREATED, $createdReferral);

        // 创建已归因的推荐关系
        $attributedReferral = new Referral();
        $attributedReferral->setId('ref-00000000000002');
        $attributedReferral->setCampaignId($activeCampaign->getId());
        $attributedReferral->setReferrerType('user');
        $attributedReferral->setReferrerId('user_123');
        $attributedReferral->setRefereeType('user');
        $attributedReferral->setRefereeId('user_502');
        $attributedReferral->setToken($activeUserToken->getToken());
        $attributedReferral->setSource('mobile_app');
        $attributedReferral->setState(ReferralState::ATTRIBUTED);
        $attributedReferral->setCreateTime($now->modify('-2 days'));
        $attributedReferral->setQualifyTime(null);
        $attributedReferral->setRewardTime(null);

        $manager->persist($attributedReferral);
        $this->addReference(self::REFERRAL_ATTRIBUTED, $attributedReferral);

        // 创建已通过资格审核的推荐关系
        $qualifiedReferral = new Referral();
        $qualifiedReferral->setId('ref-00000000000003');
        $qualifiedReferral->setCampaignId($activeCampaign->getId());
        $qualifiedReferral->setReferrerType('user');
        $qualifiedReferral->setReferrerId('user_123');
        $qualifiedReferral->setRefereeType('user');
        $qualifiedReferral->setRefereeId('user_503');
        $qualifiedReferral->setToken($activeUserToken->getToken());
        $qualifiedReferral->setSource('email_invite');
        $qualifiedReferral->setState(ReferralState::QUALIFIED);
        $qualifiedReferral->setCreateTime($now->modify('-5 days'));
        $qualifiedReferral->setQualifyTime($now->modify('-1 day'));
        $qualifiedReferral->setRewardTime(null);

        $manager->persist($qualifiedReferral);
        $this->addReference(self::REFERRAL_QUALIFIED, $qualifiedReferral);

        // 创建已发放奖励的推荐关系
        $rewardedReferral = new Referral();
        $rewardedReferral->setId('ref-00000000000004');
        $rewardedReferral->setCampaignId($activeCampaign->getId());
        $rewardedReferral->setReferrerType('user');
        $rewardedReferral->setReferrerId('user_456');
        $rewardedReferral->setRefereeType('user');
        $rewardedReferral->setRefereeId('user_504');
        $rewardedReferral->setToken(null); // 可能没有归因令牌
        $rewardedReferral->setSource('social_media');
        $rewardedReferral->setState(ReferralState::REWARDED);
        $rewardedReferral->setCreateTime($now->modify('-10 days'));
        $rewardedReferral->setQualifyTime($now->modify('-3 days'));
        $rewardedReferral->setRewardTime($now->modify('-1 day'));

        $manager->persist($rewardedReferral);
        $this->addReference(self::REFERRAL_REWARDED, $rewardedReferral);

        // 创建已撤销的推荐关系
        $revokedReferral = new Referral();
        $revokedReferral->setId('ref-00000000000005');
        $revokedReferral->setCampaignId($activeCampaign->getId());
        $revokedReferral->setReferrerType('user');
        $revokedReferral->setReferrerId('user_789');
        $revokedReferral->setRefereeType('user');
        $revokedReferral->setRefereeId('user_505');
        $revokedReferral->setToken(null);
        $revokedReferral->setSource('suspicious_activity');
        $revokedReferral->setState(ReferralState::REVOKED);
        $revokedReferral->setCreateTime($now->modify('-15 days'));
        $revokedReferral->setQualifyTime($now->modify('-12 days'));
        $revokedReferral->setRewardTime($now->modify('-10 days'));

        $manager->persist($revokedReferral);
        $this->addReference(self::REFERRAL_REVOKED, $revokedReferral);

        // 创建VIP活动的推荐关系
        $vipQualifiedReferral = new Referral();
        $vipQualifiedReferral->setId('ref-00000000000006');
        $vipQualifiedReferral->setCampaignId($vipCampaign->getId());
        $vipQualifiedReferral->setReferrerType('user');
        $vipQualifiedReferral->setReferrerId('user_456');
        $vipQualifiedReferral->setRefereeType('user');
        $vipQualifiedReferral->setRefereeId('user_506');
        $vipQualifiedReferral->setToken($vipUserToken->getToken());
        $vipQualifiedReferral->setSource('vip_exclusive');
        $vipQualifiedReferral->setState(ReferralState::QUALIFIED);
        $vipQualifiedReferral->setCreateTime($now->modify('-7 days'));
        $vipQualifiedReferral->setQualifyTime($now->modify('-2 days'));
        $vipQualifiedReferral->setRewardTime(null);

        $manager->persist($vipQualifiedReferral);
        $this->addReference(self::REFERRAL_VIP_QUALIFIED, $vipQualifiedReferral);

        // 创建移动端推荐关系
        $mobileReferral = new Referral();
        $mobileReferral->setId('ref-00000000000007');
        $mobileReferral->setCampaignId($activeCampaign->getId());
        $mobileReferral->setReferrerType('mobile_user');
        $mobileReferral->setReferrerId('mobile_user_101');
        $mobileReferral->setRefereeType('mobile_user');
        $mobileReferral->setRefereeId('mobile_user_507');
        $mobileReferral->setToken(null);
        $mobileReferral->setSource('mobile_app_share');
        $mobileReferral->setState(ReferralState::ATTRIBUTED);
        $mobileReferral->setCreateTime($now->modify('-3 days'));
        $mobileReferral->setQualifyTime(null);
        $mobileReferral->setRewardTime(null);

        $manager->persist($mobileReferral);
        $this->addReference(self::REFERRAL_MOBILE_APP, $mobileReferral);

        // 创建首次归因活动的推荐关系
        $firstTouchReferral = new Referral();
        $firstTouchReferral->setId('ref-00000000000008');
        $firstTouchReferral->setCampaignId($firstCampaign->getId());
        $firstTouchReferral->setReferrerType('user');
        $firstTouchReferral->setReferrerId('user_303');
        $firstTouchReferral->setRefereeType('user');
        $firstTouchReferral->setRefereeId('user_508');
        $firstTouchReferral->setToken(null);
        $firstTouchReferral->setSource('first_time_bonus');
        $firstTouchReferral->setState(ReferralState::QUALIFIED);
        $firstTouchReferral->setCreateTime($now->modify('-4 days'));
        $firstTouchReferral->setQualifyTime($now->modify('-1 day'));
        $firstTouchReferral->setRewardTime(null);

        $manager->persist($firstTouchReferral);
        $this->addReference(self::REFERRAL_FIRST_TOUCH, $firstTouchReferral);

        // 创建员工活动的推荐关系
        $employeeReferral = new Referral();
        $employeeReferral->setId('ref-00000000000009');
        $employeeReferral->setCampaignId($internalCampaign->getId());
        $employeeReferral->setReferrerType('employee');
        $employeeReferral->setReferrerId('employee_404');
        $employeeReferral->setRefereeType('user');
        $employeeReferral->setRefereeId('user_509');
        $employeeReferral->setToken(null);
        $employeeReferral->setSource('employee_program');
        $employeeReferral->setState(ReferralState::REWARDED);
        $employeeReferral->setCreateTime($now->modify('-20 days'));
        $employeeReferral->setQualifyTime($now->modify('-15 days'));
        $employeeReferral->setRewardTime($now->modify('-10 days'));

        $manager->persist($employeeReferral);
        $this->addReference(self::REFERRAL_EMPLOYEE, $employeeReferral);

        $manager->flush();
    }

    /**
     * @return array<class-string<Fixture>>
     */
    public function getDependencies(): array
    {
        return [
            CampaignFixtures::class,
            AttributionTokenFixtures::class,
        ];
    }
}
