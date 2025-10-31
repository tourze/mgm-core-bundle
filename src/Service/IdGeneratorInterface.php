<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Service;

interface IdGeneratorInterface
{
    public function generate(): string;
}
