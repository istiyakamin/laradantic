<?php

declare(strict_types=1);

namespace LaraDantic\Schema;

use LaraDantic\Exceptions\SchemaException;
use LaraDantic\Schema\Reflection\PropertyReflection;
use LaraDantic\Schema\Reflection\ResolvedType;
use ValueError;

/**
 * Hydrates raw input values into the typed value expected by a schema property.
 */
final class SchemaDeserializer
{
    public static function hydrate(PropertyReflection $property, mixed $value, string $ownerClass): mixed
    {
        $type = $property->type();

        if ($value === null) {
            if ($type->nullable) {
                return null;
            }

            throw SchemaException::invalidPropertyType($property->name(), $ownerClass, $type->kind, 'null');
        }

        return match (true) {
            $type->isMixed() => $value,
            $type->isPrimitive() => self::castPrimitive($type->kind, $value, $property->name(), $ownerClass),
            $type->isEnum() => self::hydrateEnum(self::requireClassName($type, $property->name(), $ownerClass), $value, $property->name(), $ownerClass),
            $type->isSchema() => self::hydrateSchema(self::requireClassName($type, $property->name(), $ownerClass), $value, $property->name(), $ownerClass),
            $type->isArray() => self::hydrateArray($type, $value, $property->name(), $ownerClass),
            $type->kind === 'object' => self::hydrateObject(self::requireClassName($type, $property->name(), $ownerClass), $value, $property->name(), $ownerClass),
            default => throw SchemaException::unsupportedType($property->name(), $ownerClass, $type->kind),
        };
    }

    private static function requireClassName(ResolvedType $type, string $property, string $ownerClass): string
    {
        return $type->className ?? throw SchemaException::unsupportedType($property, $ownerClass, $type->kind);
    }

    private static function castPrimitive(string $kind, mixed $value, string $property, string $ownerClass): mixed
    {
        return match ($kind) {
            'string' => self::castString($value, $property, $ownerClass),
            'int' => self::castInt($value, $property, $ownerClass),
            'float' => self::castFloat($value, $property, $ownerClass),
            'bool' => self::castBool($value, $property, $ownerClass),
            default => throw SchemaException::unsupportedType($property, $ownerClass, $kind),
        };
    }

    private static function castString(mixed $value, string $property, string $ownerClass): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        throw SchemaException::invalidPropertyType($property, $ownerClass, 'string', get_debug_type($value));
    }

    private static function castInt(mixed $value, string $property, string $ownerClass): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^-?\d+$/', $value) === 1) {
            return (int) $value;
        }

        throw SchemaException::invalidPropertyType($property, $ownerClass, 'int', get_debug_type($value));
    }

    private static function castFloat(mixed $value, string $property, string $ownerClass): float
    {
        if (is_float($value) || is_int($value)) {
            return (float) $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (float) $value;
        }

        throw SchemaException::invalidPropertyType($property, $ownerClass, 'float', get_debug_type($value));
    }

    private static function castBool(mixed $value, string $property, string $ownerClass): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === 0 || $value === 1) {
            return (bool) $value;
        }

        throw SchemaException::invalidPropertyType($property, $ownerClass, 'bool', get_debug_type($value));
    }

    private static function hydrateEnum(string $enumClass, mixed $value, string $property, string $ownerClass): mixed
    {
        if ($value instanceof $enumClass) {
            return $value;
        }

        try {
            if (is_subclass_of($enumClass, \BackedEnum::class)) {
                return $enumClass::from($value);
            }

            foreach ($enumClass::cases() as $case) {
                if ($case->name === $value) {
                    return $case;
                }
            }
        } catch (ValueError) {
            // fall through to exception below
        }

        throw SchemaException::invalidPropertyType($property, $ownerClass, $enumClass, get_debug_type($value));
    }

    private static function hydrateSchema(string $schemaClass, mixed $value, string $property, string $ownerClass): mixed
    {
        if ($value instanceof $schemaClass) {
            return $value;
        }

        if (is_array($value) && is_subclass_of($schemaClass, Schema::class)) {
            return $schemaClass::from($value);
        }

        throw SchemaException::invalidPropertyType($property, $ownerClass, $schemaClass, get_debug_type($value));
    }

    private static function hydrateObject(string $class, mixed $value, string $property, string $ownerClass): object
    {
        if ($value instanceof $class) {
            return $value;
        }

        throw SchemaException::invalidPropertyType($property, $ownerClass, $class, get_debug_type($value));
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function hydrateArray(ResolvedType $type, mixed $value, string $property, string $ownerClass): array
    {
        if (! is_array($value)) {
            throw SchemaException::invalidPropertyType($property, $ownerClass, 'array', get_debug_type($value));
        }

        if (! $type->hasTypedItems()) {
            return $value;
        }

        return array_map(
            fn (mixed $item): mixed => self::hydrateArrayItem($type, $item, $property, $ownerClass),
            $value
        );
    }

    private static function hydrateArrayItem(ResolvedType $type, mixed $item, string $property, string $ownerClass): mixed
    {
        return match ($type->itemKind) {
            'string', 'int', 'float', 'bool' => self::castPrimitive($type->itemKind, $item, $property, $ownerClass),
            'enum' => self::hydrateEnum(
                $type->itemClassName ?? throw SchemaException::unsupportedType($property, $ownerClass, 'enum'),
                $item,
                $property,
                $ownerClass
            ),
            'schema' => self::hydrateSchema(
                $type->itemClassName ?? throw SchemaException::unsupportedType($property, $ownerClass, 'schema'),
                $item,
                $property,
                $ownerClass
            ),
            default => $item,
        };
    }
}
