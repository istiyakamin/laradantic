<?php

declare(strict_types=1);

namespace LaraDantic\Schema\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_CLASS)]
final class Description
{
    public function __construct(public readonly string $text) {}
}
