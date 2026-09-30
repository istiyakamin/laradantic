<?php

declare(strict_types=1);

namespace LaraDantic\Tests\Fixtures;

use LaraDantic\Schema\Schema;

class DemoQuery extends Schema
{
    public string $answer;

    public int $confidence = 100;
}
