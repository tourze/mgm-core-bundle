<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Entity;

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\MgmCoreBundle\Enum\Attribution;

#[ORM\Entity]
#[ORM\Table(
    name: 'mgm_campaigns',
    options: ['comment' => 'MGM 活动表，存储推荐活动的配置信息']
)]
// Single-column indexes defined on properties via IndexColumn
class Campaign implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\Column(
        type: Types::STRING,
        length: 64,
        options: ['comment' => '活动ID']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    private string $id;

    #[ORM\Column(
        type: Types::STRING,
        length: 255,
        options: ['comment' => '活动名称']
    )]
    #[IndexColumn]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $name;

    #[ORM\Column(
        type: Types::BOOLEAN,
        options: ['comment' => '活动是否激活']
    )]
    #[IndexColumn]
    #[Assert\Type(type: 'bool')]
    private bool $active;

    /** @var array<string, mixed> */
    #[ORM\Column(
        type: Types::JSON,
        options: ['comment' => '活动配置JSON']
    )]
    #[Assert\Type(type: 'array')]
    private array $configJson = [];

    #[ORM\Column(
        type: Types::INTEGER,
        options: ['comment' => '活动有效天数']
    )]
    #[Assert\Type(type: 'int')]
    #[Assert\PositiveOrZero]
    private int $windowDays;

    #[ORM\Column(
        type: Types::STRING,
        enumType: Attribution::class,
        options: ['comment' => '归因策略']
    )]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [Attribution::class, 'cases'])]
    private Attribution $attribution;

    #[ORM\Column(
        type: Types::BOOLEAN,
        options: ['comment' => '是否禁止自推荐']
    )]
    #[Assert\Type(type: 'bool')]
    private bool $selfBlock;

    #[ORM\Column(
        type: Types::DECIMAL,
        precision: 20,
        scale: 4,
        nullable: true,
        options: ['comment' => '活动预算上限']
    )]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,4})?$/', message: '预算格式不正确')]
    private ?string $budgetLimit = null;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfigJson(): array
    {
        return $this->configJson;
    }

    /**
     * @param array<string, mixed> $configJson
     */
    public function setConfigJson(array $configJson): void
    {
        $this->configJson = $configJson;
    }

    public function getWindowDays(): int
    {
        return $this->windowDays;
    }

    public function setWindowDays(int $windowDays): void
    {
        $this->windowDays = $windowDays;
    }

    public function getAttribution(): Attribution
    {
        return $this->attribution;
    }

    public function setAttribution(Attribution $attribution): void
    {
        $this->attribution = $attribution;
    }

    public function isSelfBlock(): bool
    {
        return $this->selfBlock;
    }

    public function setSelfBlock(bool $selfBlock): void
    {
        $this->selfBlock = $selfBlock;
    }

    public function getBudgetLimit(): int|string|null
    {
        if (null === $this->budgetLimit) {
            return null;
        }
        if (1 === preg_match('/^\d+(?:\.0+)?$/', $this->budgetLimit)) {
            return (int) $this->budgetLimit;
        }

        return $this->budgetLimit;
    }

    public function setBudgetLimit(?string $budgetLimit): void
    {
        $this->budgetLimit = $budgetLimit;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
