<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Entity;

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\MgmCoreBundle\Enum\Decision;

#[ORM\Entity]
#[ORM\Table(
    name: 'mgm_qualifications',
    options: ['comment' => 'MGM 资格审核表，记录推荐的审核结果']
)]
// Single-column indexes defined via IndexColumn on properties
class Qualification implements \Stringable
{
    #[ORM\Id]
    #[ORM\Column(
        type: Types::STRING,
        length: 64,
        options: ['comment' => '资格审核ID']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    private string $id;

    #[ORM\Column(
        type: Types::STRING,
        length: 64,
        options: ['comment' => '推荐关系ID']
    )]
    #[IndexColumn]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    private string $referralId;

    #[ORM\Column(
        type: Types::STRING,
        enumType: Decision::class,
        options: ['comment' => '审核决定']
    )]
    #[IndexColumn]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [Decision::class, 'cases'])]
    private Decision $decision;

    #[ORM\Column(
        type: Types::STRING,
        length: 255,
        nullable: true,
        options: ['comment' => '审核原因']
    )]
    #[Assert\Length(max: 255)]
    private ?string $reason = null;

    /** @var array<string, mixed> */
    #[ORM\Column(
        type: Types::JSON,
        options: ['comment' => '审核证据JSON']
    )]
    #[Assert\Type(type: 'array')]
    private array $evidenceJson = [];

    #[ORM\Column(
        type: Types::DATETIME_IMMUTABLE,
        options: ['comment' => '事件发生时间']
    )]
    #[IndexColumn]
    #[Assert\NotNull]
    private \DateTimeInterface $occurTime;

    #[ORM\Column(
        type: Types::DATETIME_IMMUTABLE,
        options: ['comment' => '创建时间']
    )]
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

    public function getReferralId(): string
    {
        return $this->referralId;
    }

    public function setReferralId(string $referralId): void
    {
        $this->referralId = $referralId;
    }

    public function getDecision(): Decision
    {
        return $this->decision;
    }

    public function setDecision(Decision $decision): void
    {
        $this->decision = $decision;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): void
    {
        $this->reason = $reason;
    }

    /**
     * @return array<string, mixed>
     */
    public function getEvidenceJson(): array
    {
        return $this->evidenceJson;
    }

    /**
     * @param array<string, mixed> $evidenceJson
     */
    public function setEvidenceJson(array $evidenceJson): void
    {
        $this->evidenceJson = $evidenceJson;
    }

    public function getOccurTime(): \DateTimeInterface
    {
        return $this->occurTime;
    }

    public function setOccurTime(\DateTimeInterface $occurTime): void
    {
        $this->occurTime = $occurTime;
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
        return $this->referralId . ' - ' . $this->decision->value;
    }
}
