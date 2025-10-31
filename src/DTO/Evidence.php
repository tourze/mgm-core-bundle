<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\DTO;

use DateTimeInterface;

class Evidence
{
    public function __construct(
        public readonly string $type,
        public readonly string $id,
        public readonly \DateTimeInterface $occurTime,
        /** @var array<string, mixed> */
        public readonly array $attrs = [],
    ) {
    }
}
