<?php

declare(strict_types=1);

namespace LaraDantic\Examples\BookingAgent;

use LaraDantic\Schema\Attributes\Description;
use LaraDantic\Schema\Attributes\Example;
use LaraDantic\Schema\Attributes\Max;
use LaraDantic\Schema\Attributes\Min;
use LaraDantic\Schema\Schema;

class FlightBooking extends Schema
{
    #[Description('Departure airport IATA code')]
    #[Example('VIE')]
    public string $origin;

    #[Description('Destination airport IATA code')]
    #[Example('DAC')]
    public string $destination;

    public string $departureDate;

    public ?string $returnDate;

    #[Min(1)]
    #[Max(20)]
    public int $passengers = 1;

    public Cabin $cabin;
}
