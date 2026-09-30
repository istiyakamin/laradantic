<?php

declare(strict_types=1);

namespace LaraDantic\Schema\Attributes;

use Attribute;

/**
 * Maps to the JSON Schema `pattern` keyword (an ECMA-262 regular expression, without delimiters).
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Pattern
{
    public function __construct(public readonly string $regex) {}
}
