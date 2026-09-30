<?php

declare(strict_types=1);

namespace LaraDantic\Tests;

use LaraDantic\Laravel\LaraDanticServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaraDanticServiceProvider::class,
        ];
    }
}
