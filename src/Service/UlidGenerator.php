<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Service;

use Symfony\Component\Uid\Ulid;

class UlidGenerator implements IdGeneratorInterface
{
    public function generate(): string
    {
        return (string) new Ulid();
    }
}
