<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Entity;

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'mgm_idempotency_keys',
    options: ['comment' => 'MGM 幂等性键表，用于防止重复操作']
)]
#[ORM\UniqueConstraint(columns: ['key', 'scope'])]
class IdempotencyKey implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        type: Types::INTEGER,
        options: ['comment' => '主键ID']
    )]
    private ?int $id = null;

    #[ORM\Column(
        type: Types::STRING,
        length: 128,
        options: ['comment' => '幂等性键值']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128)]
    private string $key;

    #[ORM\Column(
        type: Types::STRING,
        length: 64,
        options: ['comment' => '作用域范围']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    private string $scope;

    /** @var array<string, mixed> */
    #[ORM\Column(
        type: Types::JSON,
        options: ['comment' => '操作结果JSON']
    )]
    #[Assert\Type(type: 'array')]
    private array $resultJson = [];

    #[ORM\Column(
        type: Types::DATETIME_IMMUTABLE,
        options: ['comment' => '创建时间']
    )]
    #[Assert\NotNull]
    private \DateTimeInterface $createTime;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    public function getScope(): string
    {
        return $this->scope;
    }

    public function setScope(string $scope): void
    {
        $this->scope = $scope;
    }

    /**
     * @return array<string, mixed>
     */
    public function getResultJson(): array
    {
        return $this->resultJson;
    }

    /**
     * @param array<string, mixed> $resultJson
     */
    public function setResultJson(array $resultJson): void
    {
        $this->resultJson = $resultJson;
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
        return $this->key;
    }
}
