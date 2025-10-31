<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Entity;

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\MgmCoreBundle\Enum\Direction;

#[ORM\Entity]
#[ORM\Table(
    name: 'mgm_ledger',
    options: ['comment' => 'MGM 账本表，记录奖励的金额变动']
)]
class Ledger implements \Stringable
{
    #[ORM\Id]
    #[ORM\Column(
        type: Types::STRING,
        length: 64,
        options: ['comment' => '账本记录ID']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    private string $id;

    #[ORM\Column(
        type: Types::STRING,
        length: 64,
        options: ['comment' => '奖励ID']
    )]
    #[IndexColumn]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    private string $rewardId;

    #[ORM\Column(
        type: Types::STRING,
        enumType: Direction::class,
        options: ['comment' => '金额方向（加减）']
    )]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [Direction::class, 'cases'])]
    private Direction $direction;

    #[ORM\Column(
        type: Types::DECIMAL,
        precision: 20,
        scale: 4,
        options: ['comment' => '金额数量']
    )]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,4})?$/', message: '金额格式不正确')]
    private string $amount;

    #[ORM\Column(
        type: Types::STRING,
        length: 3,
        options: ['comment' => '货币代码']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 3)]
    private string $currency;

    #[ORM\Column(
        type: Types::STRING,
        length: 255,
        nullable: true,
        options: ['comment' => '操作原因']
    )]
    #[Assert\Length(max: 255)]
    private ?string $reason = null;

    #[ORM\Column(
        type: Types::DATETIME_IMMUTABLE,
        options: ['comment' => '创建时间']
    )]
    #[IndexColumn]
    #[Assert\NotNull]
    private \DateTimeInterface $createTime;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getRewardId(): string
    {
        return $this->rewardId;
    }

    public function setRewardId(string $rewardId): void
    {
        $this->rewardId = $rewardId;
    }

    public function getDirection(): Direction
    {
        return $this->direction;
    }

    public function setDirection(Direction $direction): void
    {
        $this->direction = $direction;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): void
    {
        $this->amount = $amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): void
    {
        $this->reason = $reason;
    }

    public function getCreateTime(): \DateTimeInterface
    {
        return $this->createTime;
    }

    public function setCreateTime(\DateTimeInterface $createTime): void
    {
        $this->createTime = $createTime;
    }

    public function __toString(): string
    {
        return $this->direction->value . ' ' . $this->amount . ' ' . $this->currency;
    }
}
