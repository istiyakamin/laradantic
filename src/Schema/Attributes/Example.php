<?php

declare(strict_types=1);

namespace LaraDantic\Schema\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final class Example
{
    public function __construct(public readonly mixed $value) {}
}
