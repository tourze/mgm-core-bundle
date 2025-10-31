<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\MgmCoreBundle\DTO\RewardIntent;
use Tourze\MgmCoreBundle\Enum\Beneficiary;

/**
 * @internal
 */
#[CoversClass(RewardIntent::class)]
class RewardIntentTest extends TestCase
{
    public function testConstructionWithRequiredParameters(): void
    {
        $beneficiary = Beneficiary::REFERRER;
        $type = 'cash';
        $amount = '100.00';

        $intent = new RewardIntent($beneficiary, $type, $amount);

        $this->assertSame($beneficiary, $intent->beneficiary);
        $this->assertSame($type, $intent->type);
        $this->assertSame($amount, $intent->amount);
        $this->assertNull($intent->currency);
        $this->assertSame([], $intent->meta);
    }

    public function testConstructionWithAllParameters(): void
    {
        $beneficiary = Beneficiary::REFEREE;
        $type = 'points';
        $amount = '500';
        $currency = 'USD';
        $meta = [
            'campaign_id' => 'campaign-123',
            'source' => 'mobile_app',
            'tier' => 'premium',
            'nested' => [
                'level' => 2,
                'multiplier' => 1.5,
            ],
        ];

        $intent = new RewardIntent($beneficiary, $type, $amount, $currency, $meta);

        $this->assertSame($beneficiary, $intent->beneficiary);
        $this->assertSame($type, $intent->type);
        $this->assertSame($amount, $intent->amount);
        $this->assertSame($currency, $intent->currency);
        $this->assertSame($meta, $intent->meta);
    }

    public function testConstructionWithReferrerBeneficiary(): void
    {
        $beneficiary = Beneficiary::REFERRER;
        $type = 'bonus';
        $amount = '25.50';
        $currency = 'EUR';

        $intent = new RewardIntent($beneficiary, $type, $amount, $currency);

        $this->assertSame(Beneficiary::REFERRER, $intent->beneficiary);
        $this->assertSame('referrer', $intent->beneficiary->value);
        $this->assertSame($currency, $intent->currency);
    }

    public function testConstructionWithRefereeBeneficiary(): void
    {
        $beneficiary = Beneficiary::REFEREE;
        $type = 'welcome_bonus';
        $amount = '10.00';

        $intent = new RewardIntent($beneficiary, $type, $amount);

        $this->assertSame(Beneficiary::REFEREE, $intent->beneficiary);
        $this->assertSame('referee', $intent->beneficiary->value);
        $this->assertNull($intent->currency);
    }

    public function testConstructionWithEmptyStrings(): void
    {
        $beneficiary = Beneficiary::REFERRER;
        $type = '';
        $amount = '';
        $currency = '';

        $intent = new RewardIntent($beneficiary, $type, $amount, $currency);

        $this->assertSame($beneficiary, $intent->beneficiary);
        $this->assertSame($type, $intent->type);
        $this->assertSame($amount, $intent->amount);
        $this->assertSame($currency, $intent->currency);
    }

    public function testConstructionWithNullOptionalParameters(): void
    {
        $beneficiary = Beneficiary::REFEREE;
        $type = 'discount';
        $amount = '15.00';

        $intent = new RewardIntent($beneficiary, $type, $amount, null, []);

        $this->assertSame($beneficiary, $intent->beneficiary);
        $this->assertSame($type, $intent->type);
        $this->assertSame($amount, $intent->amount);
        $this->assertNull($intent->currency);
        $this->assertSame([], $intent->meta);
    }

    public function testConstructionWithComplexMetaArray(): void
    {
        $beneficiary = Beneficiary::REFERRER;
        $type = 'complex_reward';
        $amount = '75.25';
        $currency = 'GBP';
        $meta = [
            'string_value' => 'test',
            'int_value' => 123,
            'float_value' => 45.67,
            'bool_value' => true,
            'null_value' => null,
            'array_value' => [1, 2, 3],
            'nested_object' => [
                'name' => 'John',
                'preferences' => [
                    'email' => true,
                    'sms' => false,
                ],
            ],
        ];

        $intent = new RewardIntent($beneficiary, $type, $amount, $currency, $meta);

        $this->assertSame($meta, $intent->meta);
        $this->assertSame('test', $intent->meta['string_value']);
        $this->assertSame(123, $intent->meta['int_value']);
        $this->assertSame(45.67, $intent->meta['float_value']);
        $this->assertTrue($intent->meta['bool_value']);
        $this->assertArrayHasKey('null_value', $intent->meta);
        $this->assertSame(null, $intent->meta['null_value']);
        $this->assertSame([1, 2, 3], $intent->meta['array_value']);
        $this->assertSame('John', $intent->meta['nested_object']['name']);
        $this->assertTrue($intent->meta['nested_object']['preferences']['email']);
    }

    public function testDifferentRewardTypes(): void
    {
        $types = [
            'cash',
            'points',
            'discount',
            'voucher',
            'cashback',
            'credit',
            'bonus',
        ];

        foreach ($types as $type) {
            $intent = new RewardIntent(Beneficiary::REFERRER, $type, '50.00');
            $this->assertSame($type, $intent->type);
        }
    }

    public function testDifferentAmountFormats(): void
    {
        $amounts = [
            '0.00',
            '10',
            '100.50',
            '1000.99',
            '0.01',
            '9999.99',
        ];

        foreach ($amounts as $amount) {
            $intent = new RewardIntent(Beneficiary::REFEREE, 'test', $amount);
            $this->assertSame($amount, $intent->amount);
        }
    }

    public function testPropertiesAreReadonly(): void
    {
        $beneficiary = Beneficiary::REFERRER;
        $type = 'readonly_test';
        $amount = '42.00';
        $currency = 'USD';
        $meta = ['test' => 'value'];

        $intent = new RewardIntent($beneficiary, $type, $amount, $currency, $meta);

        // This test verifies that properties are readonly by checking their reflection
        $reflection = new \ReflectionClass($intent);

        $beneficiaryProperty = $reflection->getProperty('beneficiary');
        $typeProperty = $reflection->getProperty('type');
        $amountProperty = $reflection->getProperty('amount');
        $currencyProperty = $reflection->getProperty('currency');
        $metaProperty = $reflection->getProperty('meta');

        $this->assertTrue($beneficiaryProperty->isReadOnly());
        $this->assertTrue($typeProperty->isReadOnly());
        $this->assertTrue($amountProperty->isReadOnly());
        $this->assertTrue($currencyProperty->isReadOnly());
        $this->assertTrue($metaProperty->isReadOnly());
    }

    public function testConstructionWithDifferentCurrencies(): void
    {
        $currencies = ['USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD', 'CHF'];

        foreach ($currencies as $currency) {
            $intent = new RewardIntent(
                Beneficiary::REFERRER,
                'currency_test',
                '100.00',
                $currency
            );
            $this->assertSame($currency, $intent->currency);
        }
    }

    public function testBeneficiaryEnumValues(): void
    {
        $referrerIntent = new RewardIntent(Beneficiary::REFERRER, 'test', '10.00');
        $refereeIntent = new RewardIntent(Beneficiary::REFEREE, 'test', '20.00');

        $this->assertSame('referrer', $referrerIntent->beneficiary->value);
        $this->assertSame('referee', $refereeIntent->beneficiary->value);
    }
}
