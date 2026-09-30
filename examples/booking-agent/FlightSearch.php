<?php

declare(strict_types=1);

namespace LaraDantic\Examples\BookingAgent;

use LaraDantic\Schema\Schema;

class FlightSearch extends Schema
{
    public string $origin;

    public string $destination;

    public string $departureDate;
}
