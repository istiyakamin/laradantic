<?php

declare(strict_types=1);

namespace LaraDantic\Schema\Attributes;

use Attribute;

/**
 * Maps to the JSON Schema `format` keyword (e.g. "email", "uri", "date", "date-time").
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Format
{
    public function __construct(public readonly string $format) {}
}
