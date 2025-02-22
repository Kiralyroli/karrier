<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('price', [$this, 'formatPrice']),
        ];
    }

    public function formatPrice(float $number, int $decimals = 0, string $decPoint = ',', string $thousandsSep = '.'): string
    {
        $price = number_format($number, $decimals, $decPoint, $thousandsSep);
        return $price . ' Ft';
    }
}