<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Exception;

class ReferralException extends \Exception
{
    public static function selfReferralNotAllowed(): self
    {
        return new self('Self-referral not allowed');
    }

    public static function duplicateReferral(): self
    {
        return new self('Referral already exists');
    }
}
