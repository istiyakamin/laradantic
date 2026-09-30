<?php

declare(strict_types=1);

namespace LaraDantic\Schema\Attributes;

use Attribute;

/**
 * Maps to the JSON Schema `maxLength` keyword.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class MaxLength
{
    public function __construct(public readonly int $value) {}
}
