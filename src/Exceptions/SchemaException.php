<?php

declare(strict_types=1);

namespace LaraDantic\Exceptions;

class SchemaException extends LaraDanticException
{
    public static function missingRequiredProperty(string $property, string $class): self
    {
        return new self("Missing required property [{$property}] for schema [{$class}].");
    }

    public static function invalidPropertyType(string $property, string $class, string $expected, string $given): self
    {
        return new self(
            "Invalid type for property [{$property}] on schema [{$class}]: expected {$expected}, got {$given}."
        );
    }

    public static function uninitializedProperty(string $property, string $class): self
    {
        return new self("Property [{$property}] on schema [{$class}] has not been initialized.");
    }

    public static function unsupportedType(string $property, string $class, string $type): self
    {
        return new self("Unsupported type [{$type}] for property [{$property}] on schema [{$class}].");
    }
}
