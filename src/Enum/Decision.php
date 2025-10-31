<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Enum;

use Tourze\EnumExtra\BadgeInterface;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum Decision: string implements Labelable, Itemable, Selectable, BadgeInterface
{
    use ItemTrait;
    use SelectTrait;

    case QUALIFIED = 'qualified';
    case REJECTED = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::QUALIFIED => '合格',
            self::REJECTED => '拒绝',
        };
    }

    public function getBadge(): string
    {
        return match ($this) {
            self::QUALIFIED => BadgeInterface::SUCCESS,
            self::REJECTED => BadgeInterface::DANGER,
        };
    }
}
