<?php

declare(strict_types=1);

namespace LaraDantic\Schema\Attributes;

use Attribute;

/**
 * Maps to the JSON Schema `minLength` keyword.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class MinLength
{
    public function __construct(public readonly int $value) {}
}
