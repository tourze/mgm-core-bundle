<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Enum;

use Tourze\EnumExtra\BadgeInterface;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum Attribution: string implements Labelable, Itemable, Selectable, BadgeInterface
{
    use ItemTrait;
    use SelectTrait;

    case FIRST = 'first';
    case LAST = 'last';

    public function getLabel(): string
    {
        return match ($this) {
            self::FIRST => '首次触达',
            self::LAST => '最后触达',
        };
    }

    public function getBadge(): string
    {
        return match ($this) {
            self::FIRST => BadgeInterface::PRIMARY,
            self::LAST => BadgeInterface::SUCCESS,
        };
    }
}
