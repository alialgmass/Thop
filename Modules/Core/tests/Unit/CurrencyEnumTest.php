<?php

namespace Modules\Core\Tests\Unit;

use Brick\Money\Money;
use Modules\Core\Enums\CurrencyEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CurrencyEnumTest extends TestCase
{
    #[Test]
    public function the_default_currency_is_sar(): void
    {
        $this->assertSame(CurrencyEnum::SAR, CurrencyEnum::default());
    }

    #[Test]
    public function label_and_symbol_come_from_the_enum_translations(): void
    {
        $this->assertSame('ر.س', CurrencyEnum::SAR->symbol());
        $this->assertSame('Saudi Riyal', CurrencyEnum::SAR->label());
        $this->assertSame('$', CurrencyEnum::USD->symbol());
        $this->assertSame('US Dollar', CurrencyEnum::USD->label());
    }

    #[Test]
    public function money_is_formatted_with_the_currency_symbol_and_two_decimals(): void
    {
        $money = Money::of(1234.5, 'SAR');

        $this->assertSame('ر.س1,234.50', CurrencyEnum::format($money));
    }

    #[Test]
    public function to_array_exposes_the_full_catalog(): void
    {
        $this->assertSame([
            'SAR' => [
                'code' => 'SAR',
                'symbol' => 'ر.س',
                'label' => 'Saudi Riyal',
            ],
            'USD' => [
                'code' => 'USD',
                'symbol' => '$',
                'label' => 'US Dollar',
            ],
        ], CurrencyEnum::toArray());
    }
}
