<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Service;

use Tourze\MgmCoreBundle\DTO\IssueResult;
use Tourze\MgmCoreBundle\DTO\RewardIntent;

interface RewardIssuerInterface
{
    public function issue(RewardIntent $intent, string $idemKey): IssueResult;
}
