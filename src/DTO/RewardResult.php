<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\DTO;

class RewardResult
{
    public function __construct(
        public readonly string $status,
        public readonly ?string $rewardId = null,
        public readonly ?string $reason = null,
    ) {
    }
}
