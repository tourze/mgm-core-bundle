<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\DTO;

class IssueResult
{
    public function __construct(
        public readonly string $status,
        public readonly ?string $externalIssueId = null,
        public readonly ?string $reason = null,
    ) {
    }
}
