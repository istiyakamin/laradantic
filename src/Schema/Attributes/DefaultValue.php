<?php

declare(strict_types=1);

namespace LaraDantic\Schema\Attributes;

use Attribute;

/**
 * Declares the JSON Schema `default` for a property explicitly, for cases
 * where it should differ from (or exist without) a native PHP default value.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class DefaultValue
{
    public function __construct(public readonly mixed $value) {}
}
