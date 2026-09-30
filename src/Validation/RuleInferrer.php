<?php

declare(strict_types=1);

namespace LaraDantic\Validation;

use Illuminate\Validation\Rule;
use LaraDantic\Exceptions\UnsupportedFeatureException;
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
use ReflectionEnum;

/**
 * Infers Laravel validation rules from a schema's reflected properties and attributes.
 */
final class RuleInferrer
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function infer(string $schemaClass): array
    {
        return self::inferWithPrefix($schemaClass, '');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private static function inferWithPrefix(string $schemaClass, string $prefix): array
    {
        if (! is_subclass_of($schemaClass, Schema::class)) {
            throw UnsupportedFeatureException::notASchemaClass($schemaClass);
        }

        $reflection = SchemaReflection::for($schemaClass);
        $rules = [];

        foreach ($reflection->properties() as $property) {
            $field = $prefix.$property->name();
            [$fieldRules, $nested] = self::propertyRules($property, $field, $schemaClass);

            $rules[$field] = $fieldRules;
            $rules += $nested;
        }

        return $rules;
    }

    /**
     * @return array{0: array<int, mixed>, 1: array<string, array<int, mixed>>}
     */
    private static function propertyRules(PropertyReflection $property, string $field, string $ownerClass): array
    {
        $type = $property->type();
        $rules = [self::presence($property)];
        $nested = [];

        if ($type->isPrimitive()) {
            $rules[] = self::primitiveRule($type->kind);
        } elseif ($type->isEnum()) {
            $rules[] = self::enumRule(self::requireClassName($type, $property, $ownerClass));
        } elseif ($type->isSchema()) {
            $rules[] = 'array';
            $nested = self::inferWithPrefix(self::requireClassName($type, $property, $ownerClass), $field.'.');
        } elseif ($type->isArray()) {
            $rules[] = 'array';
            $nested = self::arrayItemRules($type, $field, $property, $ownerClass);
        }

        array_push($rules, ...self::attributeRules($property));

        return [$rules, $nested];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private static function arrayItemRules(ResolvedType $type, string $field, PropertyReflection $property, string $ownerClass): array
    {
        if (! $type->hasTypedItems()) {
            return [];
        }

        $itemField = $field.'.*';

        return match ($type->itemKind) {
            'string', 'int', 'float', 'bool' => [$itemField => [self::primitiveRule($type->itemKind)]],
            'enum' => [$itemField => [self::enumRule(
                $type->itemClassName ?? throw UnsupportedFeatureException::jsonSchemaForType($property->name(), $ownerClass, 'enum')
            )]],
            'schema' => [$itemField => ['array']] + self::inferWithPrefix(
                $type->itemClassName ?? throw UnsupportedFeatureException::jsonSchemaForType($property->name(), $ownerClass, 'schema'),
                $itemField.'.'
            ),
            default => [],
        };
    }

    private static function presence(PropertyReflection $property): string
    {
        return match (true) {
            $property->isRequired() => 'required',
            $property->type()->nullable => 'nullable',
            default => 'sometimes',
        };
    }

    private static function primitiveRule(string $kind): string
    {
        return match ($kind) {
            'string' => 'string',
            'int' => 'integer',
            'float' => 'numeric',
            'bool' => 'boolean',
            default => throw new \InvalidArgumentException("Unknown primitive kind [{$kind}]."),
        };
    }

    private static function enumRule(string $enumClass): mixed
    {
        if (! enum_exists($enumClass)) {
            throw UnsupportedFeatureException::notAnEnumClass($enumClass);
        }

        $reflectionEnum = new ReflectionEnum($enumClass);

        $values = $reflectionEnum->isBacked()
            ? array_map(static fn ($case) => $case->getBackingValue(), $reflectionEnum->getCases())
            : array_map(static fn ($case) => $case->getName(), $reflectionEnum->getCases());

        return Rule::in($values);
    }

    /**
     * @return array<int, string>
     */
    private static function attributeRules(PropertyReflection $property): array
    {
        $raw = $property->raw();
        $rules = [];

        if (($attribute = $raw->getAttributes(Min::class)[0] ?? null) !== null) {
            $rules[] = 'min:'.$attribute->newInstance()->value;
        }

        if (($attribute = $raw->getAttributes(Max::class)[0] ?? null) !== null) {
            $rules[] = 'max:'.$attribute->newInstance()->value;
        }

        if (($attribute = $raw->getAttributes(MinLength::class)[0] ?? null) !== null) {
            $rules[] = 'min:'.$attribute->newInstance()->value;
        }

        if (($attribute = $raw->getAttributes(MaxLength::class)[0] ?? null) !== null) {
            $rules[] = 'max:'.$attribute->newInstance()->value;
        }

        if (($attribute = $raw->getAttributes(Pattern::class)[0] ?? null) !== null) {
            $rules[] = 'regex:/'.$attribute->newInstance()->regex.'/';
        }

        if (($attribute = $raw->getAttributes(Format::class)[0] ?? null) !== null) {
            $mapped = self::formatRule($attribute->newInstance()->format);

            if ($mapped !== null) {
                $rules[] = $mapped;
            }
        }

        return $rules;
    }

    private static function formatRule(string $format): ?string
    {
        return match ($format) {
            'email' => 'email',
            'url', 'uri' => 'url',
            'uuid' => 'uuid',
            'date' => 'date',
            'date-time', 'datetime' => 'date',
            'ipv4' => 'ipv4',
            'ipv6' => 'ipv6',
            default => null,
        };
    }

    private static function requireClassName(ResolvedType $type, PropertyReflection $property, string $ownerClass): string
    {
        return $type->className ?? throw UnsupportedFeatureException::jsonSchemaForType($property->name(), $ownerClass, $type->kind);
    }
}
