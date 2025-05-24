<?php

namespace App\Service;

class CurrencyHelper
{
    public const CURRENCY_RATES = [
        'USD' => 1.0,
        'EUR' => 0.9215,
        'BTC' => 0.000012,
    ];

    public function getRate(string $currency): float
    {
        return self::CURRENCY_RATES[$currency] ?? 1.0;
    }

    public function getRates(): array
    {
        return self::CURRENCY_RATES;
    }

    public function convert(float $amount, string $from, string $to): float
    {
        $fromRate = $this->getRate($from);
        $toRate = $this->getRate($to);

        return round($amount * ($toRate / $fromRate), 8);
    }
}
