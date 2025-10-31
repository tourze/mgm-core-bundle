<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Service;

use DateTime;
use DateTimeInterface;

class SystemClock implements ClockInterface
{
    public function now(): \DateTimeInterface
    {
        return new \DateTimeImmutable();
    }
}
