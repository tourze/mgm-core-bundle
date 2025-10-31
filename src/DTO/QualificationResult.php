<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\DTO;

class QualificationResult
{
    public function __construct(
        public readonly string $status,
        public readonly ?string $reason = null,
        public readonly ?string $referralId = null,
    ) {
    }
}
