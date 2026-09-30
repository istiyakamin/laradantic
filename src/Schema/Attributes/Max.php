<?php

declare(strict_types=1);

namespace LaraDantic\Schema\Attributes;

use Attribute;

/**
 * Maps to the JSON Schema `maximum` keyword.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Max
{
    public function __construct(public readonly int|float $value) {}
}
