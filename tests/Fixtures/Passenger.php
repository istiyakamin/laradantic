<?php

declare(strict_types=1);

namespace LaraDantic\Tests\Fixtures;

use LaraDantic\Schema\Schema;

class Passenger extends Schema
{
    public string $name;

    public string $passportNumber;
}
