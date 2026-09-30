<?php

declare(strict_types=1);

namespace LaraDantic\JsonSchema;

use BackedEnum;
use LaraDantic\Exceptions\UnsupportedFeatureException;
use LaraDantic\Schema\Attributes\DefaultValue;
use LaraDantic\Schema\Attributes\Description;
use LaraDantic\Schema\Attributes\Example;
use LaraDantic\Schema\Attributes\Format;
use LaraDantic\Schema\Attributes\Max;
use LaraDantic\Schema\Attributes\MaxLength;
use LaraDantic\Schema\Attributes\Min;
use LaraDantic\Schema\Attributes\MinLength;
use LaraDantic\Schema\Attributes\Pattern;
use LaraDantic\Schema\Reflection\PropertyReflection;
use LaraDantic\Schema\Reflection\ResolvedType;
use LaraDantic\Schema\Reflection\SchemaReflection;
use LaraDantic\Schema\Schema;
use ReflectionClass;
use ReflectionEnum;
use UnitEnum;

/**
 * Generates a JSON Schema document from a {@see Schema} class's reflected properties.
 */
final class JsonSchemaGenerator
{
    /** @var array<string, array<string, mixed>> */
    private static array $cache = [];

    /**
     * @return array<string, mixed>
     */
    public static function generate(string $schemaClass): array
    {
        return self::$cache[$schemaClass] ??= self::build($schemaClass);
    }

    public static function clearCache(): void
    {
        self::$cache = [];
    }

    /**
     * @return array<string, mixed>
     */
    private static function build(string $schemaClass): array
    {
        if (! is_subclass_of($schemaClass, Schema::class)) {
            throw UnsupportedFeatureException::notASchemaClass($schemaClass);
        }

        $reflection = SchemaReflection::for($schemaClass);

        $properties = [];
        $required = [];

        foreach ($reflection->properties() as $property) {
            $properties[$property->name()] = self::propertySchema($property, $schemaClass);

            if ($property->isRequired()) {
                $required[] = $property->name();
            }
        }

        $schema = ['type' => JsonSchemaType::Object->value];

        $description = self::classDescription($schemaClass);
        if ($description !== null) {
            $schema['description'] = $description;
        }

        $schema['properties'] = $properties;

        if ($required !== []) {
            $schema['required'] = $required;
        }

        return $schema;
    }

    /**
     * @return array<string, mixed>
     */
    private static function propertySchema(PropertyReflection $property, string $ownerClass): array
    {
        $type = $property->type();

        $schema = match (true) {
            $type->isMixed() => [],
            $type->isPrimitive() => ['type' => JsonSchemaType::fromPrimitiveKind($type->kind)->value],
            $type->isEnum() => self::enumSchema(self::requireClassName($type, $property, $ownerClass)),
            $type->isSchema() => self::generate(self::requireClassName($type, $property, $ownerClass)),
            $type->isArray() => self::arraySchema($type, $property, $ownerClass),
            default => throw UnsupportedFeatureException::jsonSchemaForType($property->name(), $ownerClass, $type->kind),
        };

        if ($type->nullable) {
            $schema['nullable'] = true;
        }

        $schema = array_merge($schema, self::attributeSchema($property));

        if (! array_key_exists('default', $schema) && $property->hasDefault()) {
            $schema['default'] = self::normalizeValue($property->defaultValue());
        }

        return $schema;
    }

    /**
     * @return array<string, mixed>
     */
    private static function arraySchema(ResolvedType $type, PropertyReflection $property, string $ownerClass): array
    {
        $schema = ['type' => JsonSchemaType::Array->value];

        if (! $type->hasTypedItems()) {
            return $schema;
        }

        $itemKind = $type->itemKind;

        $schema['items'] = match ($itemKind) {
            'string', 'int', 'float', 'bool' => ['type' => JsonSchemaType::fromPrimitiveKind($itemKind)->value],
            'enum' => self::enumSchema(
                $type->itemClassName ?? throw UnsupportedFeatureException::jsonSchemaForType($property->name(), $ownerClass, 'enum')
            ),
            'schema' => self::generate(
                $type->itemClassName ?? throw UnsupportedFeatureException::jsonSchemaForType($property->name(), $ownerClass, 'schema')
            ),
            default => throw UnsupportedFeatureException::jsonSchemaForType($property->name(), $ownerClass, (string) $itemKind),
        };

        return $schema;
    }

    /**
     * @return array<string, mixed>
     */
    private static function enumSchema(string $enumClass): array
    {
        if (! enum_exists($enumClass)) {
            throw UnsupportedFeatureException::notAnEnumClass($enumClass);
        }

        $reflectionEnum = new ReflectionEnum($enumClass);

        if ($reflectionEnum->isBacked()) {
            $backingType = (string) $reflectionEnum->getBackingType();

            return [
                'type' => JsonSchemaType::fromPrimitiveKind($backingType)->value,
                'enum' => array_map(
                    static fn ($case) => $case->getBackingValue(),
                    $reflectionEnum->getCases()
                ),
            ];
        }

        return [
            'type' => JsonSchemaType::String->value,
            'enum' => array_map(
                static fn ($case) => $case->getName(),
                $reflectionEnum->getCases()
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function attributeSchema(PropertyReflection $property): array
    {
        $raw = $property->raw();
        $extra = [];

        $descriptions = $raw->getAttributes(Description::class);
        if ($descriptions !== []) {
            $extra['description'] = $descriptions[0]->newInstance()->text;
        }

        $examples = array_map(
            static fn ($attribute) => $attribute->newInstance()->value,
            $raw->getAttributes(Example::class)
        );
        if ($examples !== []) {
            $extra['examples'] = $examples;
        }

        if (($attribute = $raw->getAttributes(Min::class)[0] ?? null) !== null) {
            $extra['minimum'] = $attribute->newInstance()->value;
        }

        if (($attribute = $raw->getAttributes(Max::class)[0] ?? null) !== null) {
            $extra['maximum'] = $attribute->newInstance()->value;
        }

        if (($attribute = $raw->getAttributes(MinLength::class)[0] ?? null) !== null) {
            $extra['minLength'] = $attribute->newInstance()->value;
        }

        if (($attribute = $raw->getAttributes(MaxLength::class)[0] ?? null) !== null) {
            $extra['maxLength'] = $attribute->newInstance()->value;
        }

        if (($attribute = $raw->getAttributes(Pattern::class)[0] ?? null) !== null) {
            $extra['pattern'] = $attribute->newInstance()->regex;
        }

        if (($attribute = $raw->getAttributes(Format::class)[0] ?? null) !== null) {
            $extra['format'] = $attribute->newInstance()->format;
        }

        $defaults = $raw->getAttributes(DefaultValue::class);
        if ($defaults !== []) {
            $extra['default'] = self::normalizeValue($defaults[0]->newInstance()->value);
        }

        return $extra;
    }

    private static function classDescription(string $schemaClass): ?string
    {
        if (! class_exists($schemaClass)) {
            return null;
        }

        $attributes = (new ReflectionClass($schemaClass))->getAttributes(Description::class);

        return $attributes === [] ? null : $attributes[0]->newInstance()->text;
    }

    private static function requireClassName(ResolvedType $type, PropertyReflection $property, string $ownerClass): string
    {
        return $type->className ?? throw UnsupportedFeatureException::jsonSchemaForType($property->name(), $ownerClass, $type->kind);
    }

    private static function normalizeValue(mixed $value): mixed
    {
        return match (true) {
            $value instanceof Schema => $value->toArray(),
            $value instanceof BackedEnum => $value->value,
            $value instanceof UnitEnum => $value->name,
            is_array($value) => array_map(self::normalizeValue(...), $value),
            default => $value,
        };
    }
}
