<?php

namespace Modules\Core\Enums;

use Brick\Money\Money;

enum CurrencyEnum: string
{
    case SAR = 'SAR';
    case USD = 'USD';

    public static function default(): CurrencyEnum
    {
        return self::SAR;
    }

    public static function format(Money $money): string
    {
        $currency = self::fromMoney($money);

        return str($currency->symbol())->append(number_format($money->getAmount()->toFloat(), 2));
    }

    public static function fromMoney(Money $money): CurrencyEnum
    {
        return self::from($money->getCurrency()->getCurrencyCode());
    }

    public function symbol(): string
    {
        return __("enum.currency.{$this->value}.symbol");
    }

    public function label(): string
    {
        return __("enum.currency.{$this->value}.label");
    }

    public function toResponse(): array
    {
        return [
            'code' => $this->value,
            'symbol' => $this->symbol(),
            'label' => $this->label(),
        ];
    }

    public static function toArray(): array
    {
        $array = [];

        foreach (self::cases() as $enum) {
            $array[$enum->value] = $enum->toResponse();
        }

        return $array;
    }
}
