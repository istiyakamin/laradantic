<?php

declare(strict_types=1);

namespace LaraDantic\JsonSchema;

use LaraDantic\Schema\Reflection\ResolvedType;

/**
 * The primitive JSON Schema `type` values this package can produce.
 */
enum JsonSchemaType: string
{
    case String = 'string';
    case Integer = 'integer';
    case Number = 'number';
    case Boolean = 'boolean';
    case Array = 'array';
    case Object = 'object';

    /**
     * Map a {@see ResolvedType} primitive kind
     * ("string", "int", "float", "bool") to its JSON Schema type.
     */
    public static function fromPrimitiveKind(string $kind): self
    {
        return match ($kind) {
            'string' => self::String,
            'int' => self::Integer,
            'float' => self::Number,
            'bool' => self::Boolean,
            default => throw new \InvalidArgumentException("Unknown primitive kind [{$kind}]."),
        };
    }
}
