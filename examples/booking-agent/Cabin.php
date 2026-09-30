<?php

declare(strict_types=1);

namespace LaraDantic\Examples\BookingAgent;

enum Cabin: string
{
    case Economy = 'economy';
    case PremiumEconomy = 'premium_economy';
    case Business = 'business';
    case First = 'first';
}
