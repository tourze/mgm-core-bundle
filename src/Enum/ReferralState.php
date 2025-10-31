<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Enum;

use Tourze\EnumExtra\BadgeInterface;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum ReferralState: string implements Labelable, Itemable, Selectable, BadgeInterface
{
    use ItemTrait;
    use SelectTrait;
    case CREATED = 'created';
    case ATTRIBUTED = 'attributed';
    case QUALIFIED = 'qualified';
    case REWARDED = 'rewarded';
    case REVOKED = 'revoked';

    public function getLabel(): string
    {
        return match ($this) {
            self::CREATED => '创建',
            self::ATTRIBUTED => '已归因',
            self::QUALIFIED => '已合格',
            self::REWARDED => '已发放',
            self::REVOKED => '已撤销',
        };
    }

    public function getBadge(): string
    {
        return match ($this) {
            self::CREATED => BadgeInterface::INFO,
            self::ATTRIBUTED => BadgeInterface::WARNING,
            self::QUALIFIED => BadgeInterface::PRIMARY,
            self::REWARDED => BadgeInterface::SUCCESS,
            self::REVOKED => BadgeInterface::DANGER,
        };
    }
}
