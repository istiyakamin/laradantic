<?php

declare(strict_types=1);

namespace LaraDantic\Tests\Fixtures;

use LaraDantic\Schema\Schema;

class CustomRulesBooking extends Schema
{
    public string $reference;

    public static function rules(): array
    {
        return [
            'reference' => ['required', 'string', 'starts_with:BK-'],
        ];
    }
}
