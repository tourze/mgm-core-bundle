<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\MgmCoreBundle\Entity\AttributionToken;
use Tourze\MgmCoreBundle\Entity\Campaign;

class AttributionTokenFixtures extends Fixture implements DependentFixtureInterface
{
    public const TOKEN_ACTIVE_USER = 'token_active_user';
    public const TOKEN_VIP_USER = 'token_vip_user';
    public const TOKEN_EXPIRED = 'token_expired';
    public const TOKEN_MOBILE_APP = 'token_mobile_app';
    public const TOKEN_WEB_SHARE = 'token_web_share';

    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable();

        $activeCampaign = $this->getReference(CampaignFixtures::CAMPAIGN_ACTIVE, Campaign::class);
        $vipCampaign = $this->getReference(CampaignFixtures::CAMPAIGN_LIMITED_BUDGET, Campaign::class);

        // 为春季活动创建普通用户的归因令牌
        $activeUserToken = new AttributionToken();
        $activeUserToken->setToken('spring_user123_' . bin2hex(random_bytes(16)));
        $activeUserToken->setCampaignId($activeCampaign->getId());
        $activeUserToken->setReferrerType('user');
        $activeUserToken->setReferrerId('user_123');
        $activeUserToken->setExpireTime($now->modify('+7 days'));
        $activeUserToken->setCreateTime($now);

        $manager->persist($activeUserToken);
        $this->addReference(self::TOKEN_ACTIVE_USER, $activeUserToken);

        // 为VIP活动创建VIP用户的归因令牌
        $vipUserToken = new AttributionToken();
        $vipUserToken->setToken('vip_user456_' . bin2hex(random_bytes(16)));
        $vipUserToken->setCampaignId($vipCampaign->getId());
        $vipUserToken->setReferrerType('user');
        $vipUserToken->setReferrerId('user_456');
        $vipUserToken->setExpireTime($now->modify('+30 days'));
        $vipUserToken->setCreateTime($now);

        $manager->persist($vipUserToken);
        $this->addReference(self::TOKEN_VIP_USER, $vipUserToken);

        // 创建已过期的归因令牌
        $expiredToken = new AttributionToken();
        $expiredToken->setToken('expired_user789_' . bin2hex(random_bytes(16)));
        $expiredToken->setCampaignId($activeCampaign->getId());
        $expiredToken->setReferrerType('user');
        $expiredToken->setReferrerId('user_789');
        $expiredToken->setExpireTime($now->modify('-1 day'));
        $expiredToken->setCreateTime($now->modify('-8 days'));

        $manager->persist($expiredToken);
        $this->addReference(self::TOKEN_EXPIRED, $expiredToken);

        // 为移动端用户创建归因令牌
        $mobileToken = new AttributionToken();
        $mobileToken->setToken('mobile_user101_' . bin2hex(random_bytes(16)));
        $mobileToken->setCampaignId($activeCampaign->getId());
        $mobileToken->setReferrerType('mobile_user');
        $mobileToken->setReferrerId('mobile_user_101');
        $mobileToken->setExpireTime($now->modify('+7 days'));
        $mobileToken->setCreateTime($now);

        $manager->persist($mobileToken);
        $this->addReference(self::TOKEN_MOBILE_APP, $mobileToken);

        // 为网页端分享创建归因令牌
        $webShareToken = new AttributionToken();
        $webShareToken->setToken('web_share_202_' . bin2hex(random_bytes(16)));
        $webShareToken->setCampaignId($activeCampaign->getId());
        $webShareToken->setReferrerType('web_user');
        $webShareToken->setReferrerId('web_user_202');
        $webShareToken->setExpireTime($now->modify('+7 days'));
        $webShareToken->setCreateTime($now);

        $manager->persist($webShareToken);
        $this->addReference(self::TOKEN_WEB_SHARE, $webShareToken);

        // 为首次归因活动创建令牌
        $firstCampaign = $this->getReference(CampaignFixtures::CAMPAIGN_FIRST_ATTRIBUTION, Campaign::class);

        $firstTouchToken = new AttributionToken();
        $firstTouchToken->setToken('first_touch_303_' . bin2hex(random_bytes(16)));
        $firstTouchToken->setCampaignId($firstCampaign->getId());
        $firstTouchToken->setReferrerType('user');
        $firstTouchToken->setReferrerId('user_303');
        $firstTouchToken->setExpireTime($now->modify('+7 days'));
        $firstTouchToken->setCreateTime($now);

        $manager->persist($firstTouchToken);

        // 为员工活动创建令牌
        $internalCampaign = $this->getReference(CampaignFixtures::CAMPAIGN_NO_SELF_BLOCK, Campaign::class);

        $employeeToken = new AttributionToken();
        $employeeToken->setToken('employee_404_' . bin2hex(random_bytes(16)));
        $employeeToken->setCampaignId($internalCampaign->getId());
        $employeeToken->setReferrerType('employee');
        $employeeToken->setReferrerId('employee_404');
        $employeeToken->setExpireTime($now->modify('+365 days'));
        $employeeToken->setCreateTime($now);

        $manager->persist($employeeToken);

        $manager->flush();
    }

    /**
     * @return array<class-string<Fixture>>
     */
    public function getDependencies(): array
    {
        return [
            CampaignFixtures::class,
        ];
    }
}
