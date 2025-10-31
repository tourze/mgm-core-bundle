<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\DTO;

use Tourze\MgmCoreBundle\Enum\Beneficiary;

class RewardIntent
{
    public function __construct(
        public readonly Beneficiary $beneficiary,
        public readonly string $type,
        public readonly string $amount,
        public readonly ?string $currency = null,
        /** @var array<string, mixed> */
        public readonly array $meta = [],
    ) {
    }
}
