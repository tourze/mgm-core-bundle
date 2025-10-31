<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Enum;

use Tourze\EnumExtra\BadgeInterface;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum RewardState: string implements Labelable, Itemable, Selectable, BadgeInterface
{
    use ItemTrait;
    use SelectTrait;
    case PENDING = 'pending';
    case GRANTED = 'granted';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '待发放',
            self::GRANTED => '已发放',
            self::CANCELLED => '已取消',
        };
    }

    public function getBadge(): string
    {
        return match ($this) {
            self::PENDING => BadgeInterface::WARNING,
            self::GRANTED => BadgeInterface::SUCCESS,
            self::CANCELLED => BadgeInterface::DANGER,
        };
    }
}
