<?php

declare(strict_types=1);

namespace LaraDantic\Tests\Fixtures;

use LaraDantic\Schema\Schema;

class FlightSearchQuery extends Schema
{
    public string $origin;

    public string $destination;
}
