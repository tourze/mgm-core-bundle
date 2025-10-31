<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Entity;

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\MgmCoreBundle\Enum\ReferralState;

#[ORM\Entity]
#[ORM\Table(
    name: 'mgm_referrals',
    options: ['comment' => 'MGM 推荐关系表，存储用户间的推荐关系']
)]
#[ORM\UniqueConstraint(columns: ['campaign_id', 'referrer_type', 'referrer_id', 'referee_type', 'referee_id'])]
#[ORM\Index(columns: ['campaign_id', 'referrer_type', 'referrer_id', 'state'])]
#[ORM\Index(columns: ['campaign_id', 'referee_type', 'referee_id'])]
class Referral implements \Stringable
{
    #[ORM\Id]
    #[ORM\Column(
        type: Types::STRING,
        length: 64,
        options: ['comment' => '推荐关系ID']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    private string $id;

    #[ORM\Column(
        type: Types::STRING,
        length: 64,
        options: ['comment' => '活动ID']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    private string $campaignId;

    #[ORM\Column(
        type: Types::STRING,
        length: 32,
        options: ['comment' => '推荐人类型']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 32)]
    private string $referrerType;

    #[ORM\Column(
        type: Types::STRING,
        length: 64,
        options: ['comment' => '推荐人ID']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    private string $referrerId;

    #[ORM\Column(
        type: Types::STRING,
        length: 32,
        options: ['comment' => '被推荐人类型']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 32)]
    private string $refereeType;

    #[ORM\Column(
        type: Types::STRING,
        length: 64,
        options: ['comment' => '被推荐人ID']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    private string $refereeId;

    #[ORM\Column(
        type: Types::STRING,
        length: 128,
        nullable: true,
        options: ['comment' => '归因令牌']
    )]
    #[Assert\Length(max: 128)]
    private ?string $token = null;

    #[ORM\Column(
        type: Types::STRING,
        length: 64,
        options: ['comment' => '推荐来源']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    private string $source;

    #[ORM\Column(
        type: Types::STRING,
        enumType: ReferralState::class,
        options: ['comment' => '推荐状态']
    )]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [ReferralState::class, 'cases'])]
    private ReferralState $state;

    #[ORM\Column(
        type: Types::DATETIME_IMMUTABLE,
        options: ['comment' => '创建时间']
    )]
    #[Assert\NotNull]
    private \DateTimeInterface $createTime;

    #[ORM\Column(
        type: Types::DATETIME_IMMUTABLE,
        nullable: true,
        options: ['comment' => '资格验证时间']
    )]
    #[Assert\Type(type: \DateTimeInterface::class)]
    private ?\DateTimeInterface $qualifyTime = null;

    #[ORM\Column(
        type: Types::DATETIME_IMMUTABLE,
        nullable: true,
        options: ['comment' => '奖励时间']
    )]
    #[Assert\Type(type: \DateTimeInterface::class)]
    private ?\DateTimeInterface $rewardTime = null;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getCampaignId(): string
    {
        return $this->campaignId;
    }

    public function setCampaignId(string $campaignId): void
    {
        $this->campaignId = $campaignId;
    }

    public function getReferrerType(): string
    {
        return $this->referrerType;
    }

    public function setReferrerType(string $referrerType): void
    {
        $this->referrerType = $referrerType;
    }

    public function getReferrerId(): string
    {
        return $this->referrerId;
    }

    public function setReferrerId(string $referrerId): void
    {
        $this->referrerId = $referrerId;
    }

    public function getRefereeType(): string
    {
        return $this->refereeType;
    }

    public function setRefereeType(string $refereeType): void
    {
        $this->refereeType = $refereeType;
    }

    public function getRefereeId(): string
    {
        return $this->refereeId;
    }

    public function setRefereeId(string $refereeId): void
    {
        $this->refereeId = $refereeId;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): void
    {
        $this->token = $token;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): void
    {
        $this->source = $source;
    }

    public function getState(): ReferralState
    {
        return $this->state;
    }

    public function setState(ReferralState $state): void
    {
        $this->state = $state;
    }

    public function getCreateTime(): \DateTimeInterface
    {
        return $this->createTime;
    }

    public function setCreateTime(\DateTimeInterface $createTime): void
    {
        $this->createTime = $createTime;
    }

    public function getQualifyTime(): ?\DateTimeInterface
    {
        return $this->qualifyTime;
    }

    public function setQualifyTime(?\DateTimeInterface $qualifyTime): void
    {
        $this->qualifyTime = $qualifyTime;
    }

    public function getRewardTime(): ?\DateTimeInterface
    {
        return $this->rewardTime;
    }

    public function setRewardTime(?\DateTimeInterface $rewardTime): void
    {
        $this->rewardTime = $rewardTime;
    }

    public function __toString(): string
    {
        if (!isset($this->referrerType, $this->referrerId, $this->refereeType, $this->refereeId)) {
            return $this->campaignId ?? '';
        }

        return sprintf(
            '%s:%s -> %s:%s',
            $this->referrerType,
            $this->referrerId,
            $this->refereeType,
            $this->refereeId
        );
    }
}
