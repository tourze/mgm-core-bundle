<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Entity;

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\MgmCoreBundle\Enum\Beneficiary;
use Tourze\MgmCoreBundle\Enum\RewardState;

#[ORM\Entity]
#[ORM\Table(
    name: 'mgm_rewards',
    options: ['comment' => 'MGM 奖励表，存储发放的奖励信息']
)]
#[ORM\UniqueConstraint(columns: ['idem_key'])]
#[ORM\Index(columns: ['referral_id', 'state'])]
#[ORM\Index(columns: ['beneficiary', 'state'])]
class Reward implements \Stringable
{
    #[ORM\Id]
    #[ORM\Column(
        type: Types::STRING,
        length: 64,
        options: ['comment' => '奖励ID']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    private string $id;

    #[ORM\Column(
        type: Types::STRING,
        length: 64,
        options: ['comment' => '推荐关系ID']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    private string $referralId;

    #[ORM\Column(
        type: Types::STRING,
        enumType: Beneficiary::class,
        options: ['comment' => '受益人类型']
    )]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [Beneficiary::class, 'cases'])]
    private Beneficiary $beneficiary;

    #[ORM\Column(
        type: Types::STRING,
        length: 32,
        options: ['comment' => '受益人具体类型']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 32)]
    private string $beneficiaryType;

    #[ORM\Column(
        type: Types::STRING,
        length: 64,
        options: ['comment' => '受益人ID']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    private string $beneficiaryId;

    #[ORM\Column(
        type: Types::STRING,
        length: 16,
        options: ['comment' => '奖励类型']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 16)]
    private string $type;

    /** @var array<string, mixed> */
    #[ORM\Column(
        type: Types::JSON,
        options: ['comment' => '奖励规格JSON']
    )]
    #[Assert\Type(type: 'array')]
    private array $specJson = [];

    #[ORM\Column(
        type: Types::STRING,
        enumType: RewardState::class,
        options: ['comment' => '奖励状态']
    )]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [RewardState::class, 'cases'])]
    private RewardState $state;

    #[ORM\Column(
        type: Types::STRING,
        length: 64,
        nullable: true,
        options: ['comment' => '外部发放ID']
    )]
    #[Assert\Length(max: 64)]
    private ?string $externalIssueId = null;

    #[ORM\Column(
        type: Types::STRING,
        length: 128,
        options: ['comment' => '幂等性键']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128)]
    private string $idemKey;

    #[ORM\Column(
        type: Types::DATETIME_IMMUTABLE,
        options: ['comment' => '创建时间']
    )]
    #[Assert\NotNull]
    private \DateTimeInterface $createTime;

    #[ORM\Column(
        type: Types::DATETIME_IMMUTABLE,
        nullable: true,
        options: ['comment' => '发放时间']
    )]
    #[Assert\Type(type: \DateTimeInterface::class)]
    private ?\DateTimeInterface $grantTime = null;

    #[ORM\Column(
        type: Types::DATETIME_IMMUTABLE,
        nullable: true,
        options: ['comment' => '撤销时间']
    )]
    #[Assert\Type(type: \DateTimeInterface::class)]
    private ?\DateTimeInterface $revokeTime = null;

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

    public function getBeneficiary(): Beneficiary
    {
        return $this->beneficiary;
    }

    public function setBeneficiary(Beneficiary $beneficiary): void
    {
        $this->beneficiary = $beneficiary;
    }

    public function getBeneficiaryType(): string
    {
        return $this->beneficiaryType;
    }

    public function setBeneficiaryType(string $beneficiaryType): void
    {
        $this->beneficiaryType = $beneficiaryType;
    }

    public function getBeneficiaryId(): string
    {
        return $this->beneficiaryId;
    }

    public function setBeneficiaryId(string $beneficiaryId): void
    {
        $this->beneficiaryId = $beneficiaryId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSpecJson(): array
    {
        return $this->specJson;
    }

    /**
     * @param array<string, mixed> $specJson
     */
    public function setSpecJson(array $specJson): void
    {
        $this->specJson = $specJson;
    }

    public function getState(): RewardState
    {
        return $this->state;
    }

    public function setState(RewardState $state): void
    {
        $this->state = $state;
    }

    public function getExternalIssueId(): ?string
    {
        return $this->externalIssueId;
    }

    public function setExternalIssueId(?string $externalIssueId): void
    {
        $this->externalIssueId = $externalIssueId;
    }

    public function getIdemKey(): string
    {
        return $this->idemKey;
    }

    public function setIdemKey(string $idemKey): void
    {
        $this->idemKey = $idemKey;
    }

    public function getCreateTime(): \DateTimeInterface
    {
        return $this->createTime;
    }

    public function setCreateTime(\DateTimeInterface $createTime): void
    {
        $this->createTime = $createTime;
    }

    public function getGrantTime(): ?\DateTimeInterface
    {
        return $this->grantTime;
    }

    public function setGrantTime(?\DateTimeInterface $grantTime): void
    {
        $this->grantTime = $grantTime;
    }

    public function getRevokeTime(): ?\DateTimeInterface
    {
        return $this->revokeTime;
    }

    public function setRevokeTime(?\DateTimeInterface $revokeTime): void
    {
        $this->revokeTime = $revokeTime;
    }

    public function __toString(): string
    {
        if (!isset($this->beneficiary, $this->type, $this->state)) {
            return $this->type ?? '';
        }

        return sprintf(
            '%s - %s (%s)',
            $this->beneficiary->value,
            $this->type,
            $this->state->value
        );
    }
}
