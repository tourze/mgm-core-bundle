<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Entity;

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;

#[ORM\Entity]
#[ORM\Table(
    name: 'mgm_attribution_tokens',
    options: ['comment' => 'MGM 归因令牌表，用于存储推荐关系的临时令牌']
)]
#[ORM\Index(columns: ['campaign_id', 'referrer_type', 'referrer_id'])]
class AttributionToken implements \Stringable
{
    #[ORM\Id]
    #[ORM\Column(
        type: Types::STRING,
        length: 128,
        options: ['comment' => '归因令牌，用于标识推荐关系']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128)]
    private string $token;

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
        type: Types::DATETIME_IMMUTABLE,
        options: ['comment' => '令牌过期时间']
    )]
    #[IndexColumn]
    #[Assert\NotNull]
    private \DateTimeInterface $expireTime;

    #[ORM\Column(
        type: Types::DATETIME_IMMUTABLE,
        options: ['comment' => '创建时间']
    )]
    #[Assert\NotNull]
    private \DateTimeInterface $createTime;

    public function getToken(): string
    {
        return $this->token;
    }

    public function getId(): string
    {
        return $this->token;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
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

    public function getExpireTime(): \DateTimeInterface
    {
        return $this->expireTime;
    }

    public function setExpireTime(\DateTimeInterface $expireTime): void
    {
        $this->expireTime = $expireTime;
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
        return $this->token;
    }
}
