<?php

declare(strict_types=1);

namespace LaraDantic\Exceptions;

class UnsupportedFeatureException extends LaraDanticException
{
    public static function jsonSchemaForType(string $property, string $class, string $type): self
    {
        return new self(
            "Cannot generate JSON Schema for property [{$property}] on schema [{$class}]: ".
            "unsupported type [{$type}]. Only primitives, nullable types, enums, nested Schema classes ".
            'and typed arrays of these can be represented.'
        );
    }

    public static function notASchemaClass(string $class): self
    {
        return new self("Cannot generate JSON Schema: [{$class}] is not a Schema subclass.");
    }

    public static function notAnEnumClass(string $class): self
    {
        return new self("Cannot generate JSON Schema: [{$class}] is not a PHP enum.");
    }
}
