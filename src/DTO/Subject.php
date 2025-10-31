<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\DTO;

class Subject
{
    public function __construct(
        public readonly string $type,
        public readonly string $id,
    ) {
    }
}
