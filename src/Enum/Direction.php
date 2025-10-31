<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Enum;

use Tourze\EnumExtra\BadgeInterface;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum Direction: string implements Labelable, Itemable, Selectable, BadgeInterface
{
    use ItemTrait;
    use SelectTrait;
    case PLUS = '+';
    case MINUS = '-';

    public function getLabel(): string
    {
        return match ($this) {
            self::PLUS => '加',
            self::MINUS => '减',
        };
    }

    public function getBadge(): string
    {
        return match ($this) {
            self::PLUS => BadgeInterface::SUCCESS,
            self::MINUS => BadgeInterface::DANGER,
        };
    }
}
