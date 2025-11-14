# MGM Core Bundle

[English](README.md) | [中文](README.zh-CN.md)

A comprehensive Member Get Member (MGM) referral system core bundle for Symfony applications. This bundle provides the essential components for building referral campaigns, tracking referrals, managing rewards, and handling qualifications.

## Features

- **Campaign Management**: Create and manage referral campaigns with configurable settings
- **Referral Tracking**: Track referral relationships and attribution (first/last touch)
- **Reward System**: Flexible reward distribution with support for different beneficiaries
- **Qualification Engine**: Determine when referrals qualify for rewards
- **Ledger System**: Complete audit trail of all reward transactions
- **Idempotency Support**: Prevent duplicate reward processing
- **Admin Interface**: EasyAdmin integration for managing MGM data
- **Doctrine Integration**: Full ORM support with optimized database schema

## Installation

```bash
composer require tourze/mgm-core-bundle
```

## Configuration

The bundle will be automatically enabled in your Symfony application. Make sure you have Doctrine properly configured:

```yaml
# config/packages/doctrine.yaml
doctrine:
    dbal:
        url: '%env(resolve:DATABASE_URL)%'
    orm:
        auto_generate_proxy_classes: true
        enable_lazy_ghost_objects: true
        report_fields_where_declared: true
        validate_xml_mapping: true
        naming_strategy: doctrine.orm.naming_strategy.underscore_number_aware
        auto_mapping: true
        mappings:
            MgmCoreBundle:
                type: attribute
                is_bundle: false
                dir: '%kernel.project_dir%/vendor/tourze/mgm-core-bundle/src/Entity'
                prefix: 'Tourze\MgmCoreBundle\Entity'
                alias: MgmCoreBundle
```

## Basic Usage

### Creating a Campaign

```php
use Tourze\MgmCoreBundle\Entity\Campaign;

$campaign = new Campaign();
$campaign->setId('summer-2024');
$campaign->setName('Summer Referral Campaign');
$campaign->setActive(true);

$entityManager->persist($campaign);
$entityManager->flush();
```

### Creating a Referral

```php
use Tourze\MgmCoreBundle\Entity\Referral;
use Tourze\MgmCoreBundle\Enum\ReferralState;

$referral = new Referral();
$referral->setCampaign($campaign);
$referral->setReferrerId('referrer-user-id');
$referral->setRefereeId('referee-user-id');
$referral->setState(ReferralState::PENDING);

$entityManager->persist($referral);
$entityManager->flush();
```

### Processing Rewards

```php
use Tourze\MgmCoreBundle\Entity\Reward;
use Tourze\MgmCoreBundle\Enum\Decision;
use Tourze\MgmCoreBundle\Enum\Beneficiary;

$reward = new Reward();
$reward->setReferral($referral);
$reward->setDecision(Decision::ACCEPTED);
$reward->setBeneficiary(Beneficiary::REFERRER);
$reward->setAmount(100); // Reward amount
$reward->setCurrency('USD');

$entityManager->persist($reward);
$entityManager->flush();
```

## Entities

### Core Entities

- **Campaign**: Represents a referral campaign with configuration
- **Referral**: Tracks the relationship between referrer and referee
- **Reward**: Manages reward distribution and decisions
- **Qualification**: Determines when referrals qualify for rewards
- **Ledger**: Audit trail for all financial transactions
- **IdempotencyKey**: Prevents duplicate processing

### DTOs

- **Subject**: Represents participants in the referral system
- **RewardIntent**: Intention to distribute a reward
- **RewardResult**: Result of reward processing
- **QualificationResult**: Result of qualification checks
- **IssueResult**: Result of reward issuance
- **Evidence**: Supporting evidence for decisions

## Enums

- **ReferralState**: PENDING, QUALIFIED, REJECTED, EXPIRED
- **Attribution**: FIRST (first touch), LAST (last touch)
- **Decision**: PENDING, ACCEPTED, REJECTED
- **Beneficiary**: REFERRER, REFEREE, BOTH
- **Direction**: INBOUND, OUTBOUND

## Administration

The bundle provides EasyAdmin controllers for managing MGM data:

- Campaign management
- Referral tracking
- Reward approval and distribution
- Ledger audit
- Qualification management

Access these through your EasyAdmin dashboard at `/admin`.

## API Endpoints

While this bundle focuses on core functionality, you can easily expose API endpoints:

```php
// Example controller
#[Route('/api/referrals')]
class ReferralApiController extends AbstractController
{
    #[Post('/')]
    public function createReferral(Request $request): Response
    {
        // Implementation using MgmCoreBundle services
    }
}
```

## Testing

Run the test suite:

```bash
vendor/bin/phpunit
```

## Database Schema

The bundle creates the following tables:

- `mgm_campaigns` - Campaign configurations
- `mgm_referrals` - Referral relationships
- `mgm_rewards` - Reward records
- `mgm_qualifications` - Qualification rules and results
- `mgm_ledger` - Financial audit trail
- `mgm_idempotency_keys` - Duplicate prevention

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Run the test suite
6. Submit a pull request

## License

This bundle is released under the MIT License. See the LICENSE file for details.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a list of changes and version history.

## Support

For bug reports and feature requests, please use the [issue tracker](https://github.com/tourze/mgm-core-bundle/issues).