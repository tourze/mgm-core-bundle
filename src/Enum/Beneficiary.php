<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Enum;

use Tourze\EnumExtra\BadgeInterface;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum Beneficiary: string implements Labelable, Itemable, Selectable, BadgeInterface
{
    use ItemTrait;
    use SelectTrait;

    case REFERRER = 'referrer';
    case REFEREE = 'referee';

    public function getLabel(): string
    {
        return match ($this) {
            self::REFERRER => '推荐人',
            self::REFEREE => '被推荐人',
        };
    }

    public function getBadge(): string
    {
        return match ($this) {
            self::REFERRER => BadgeInterface::PRIMARY,
            self::REFEREE => BadgeInterface::INFO,
        };
    }
}
