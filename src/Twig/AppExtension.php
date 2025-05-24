<?php

namespace App\Twig;

use App\Service\CurrencyHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    private $currencyHelper;

    public function __construct(CurrencyHelper $currencyHelper)
    {
        $this->currencyHelper = $currencyHelper;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('currency_rates', [$this->currencyHelper, 'getRates']),
            new TwigFunction('convert_currency', [$this->currencyHelper, 'convert']),
        ];
    }
}
