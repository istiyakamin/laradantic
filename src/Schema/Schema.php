<?php

declare(strict_types=1);

namespace LaraDantic\Schema;

use LaraDantic\Exceptions\SchemaException;
use LaraDantic\Exceptions\SchemaValidationException;
use LaraDantic\JsonSchema\JsonSchemaGenerator;
use LaraDantic\Schema\Reflection\SchemaReflection;
use LaraDantic\Validation\RuleInferrer;
use LaraDantic\Validation\ValidationResult;
use LaraDantic\Validation\Validator;
use ReflectionClass;

/**
 * Base class for typed, reflection-driven data schemas.
 */
abstract class Schema
{
    /**
     * Hydrate a new instance from raw array data.
     *
     * @param  array<string, mixed>  $data
     */
    public static function from(array $data): static
    {
        $reflection = SchemaReflection::for(static::class);

        /** @var static $instance */
        $instance = (new ReflectionClass(static::class))->newInstanceWithoutConstructor();

        foreach ($reflection->properties() as $property) {
            $name = $property->name();

            if (array_key_exists($name, $data)) {
                $property->setValue($instance, SchemaDeserializer::hydrate($property, $data[$name], static::class));

                continue;
            }

            if ($property->hasDefault()) {
                $property->setValue($instance, $property->defaultValue());

                continue;
            }

            if ($property->type()->nullable) {
                $property->setValue($instance, null);

                continue;
            }

            throw SchemaException::missingRequiredProperty($name, static::class);
        }

        return $instance;
    }

    /**
     * Serialize this schema instance back into a plain array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return SchemaSerializer::serialize($this);
    }

    /**
     * Generate the JSON Schema representation of this schema class.
     *
     * @return array<string, mixed>
     */
    public static function jsonSchema(): array
    {
        return JsonSchemaGenerator::generate(static::class);
    }

    /**
     * Generate the JSON Schema representation of this schema class, encoded as a JSON string.
     */
    public static function jsonSchemaJson(int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES): string
    {
        return json_encode(static::jsonSchema(), $flags | JSON_THROW_ON_ERROR);
    }

    /**
     * The Laravel validation rules for this schema, inferred from its reflected
     * properties and attributes. Override this method to customize or replace them.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return RuleInferrer::infer(static::class);
    }

    /**
     * Validate raw data against this schema's rules without throwing.
     *
     * @param  array<string, mixed>  $data
     */
    public static function validate(array $data): ValidationResult
    {
        return Validator::validate(static::class, $data);
    }

    /**
     * Validate raw data against this schema's rules, then hydrate it.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws SchemaValidationException if validation fails
     */
    public static function validated(array $data): static
    {
        $result = static::validate($data);

        if ($result->fails()) {
            throw SchemaValidationException::fromResult($result);
        }

        return static::from($data);
    }
}
