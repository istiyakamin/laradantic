<?php

declare(strict_types=1);

namespace LaraDantic\Tests\Fixtures;

enum Cabin: string
{
    case Economy = 'economy';
    case PremiumEconomy = 'premium_economy';
    case Business = 'business';
    case First = 'first';
}
