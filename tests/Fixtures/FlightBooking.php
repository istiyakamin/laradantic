<?php

declare(strict_types=1);

namespace LaraDantic\Tests\Fixtures;

use LaraDantic\Schema\Schema;

class FlightBooking extends Schema
{
    public string $origin;

    public string $destination;

    public string $departureDate;

    public ?string $returnDate;

    public int $passengers = 1;

    public Cabin $cabin;

    /** @var Passenger[] */
    public array $passengers_list = [];
}
