<?php

declare(strict_types=1);

namespace LaraDantic\Laravel;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string version()
 */
class LaraDanticFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'laradantic';
    }
}
