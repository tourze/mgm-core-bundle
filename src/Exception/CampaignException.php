<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Exception;

class CampaignException extends \Exception
{
    public static function campaignNotFound(string $campaignId): self
    {
        return new self("Campaign not found: {$campaignId}");
    }

    public static function campaignInactive(string $campaignId): self
    {
        return new self("Campaign is inactive: {$campaignId}");
    }
}
