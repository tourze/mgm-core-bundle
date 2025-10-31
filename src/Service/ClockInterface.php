<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Service;

use DateTimeInterface;

interface ClockInterface
{
    public function now(): \DateTimeInterface;
}
