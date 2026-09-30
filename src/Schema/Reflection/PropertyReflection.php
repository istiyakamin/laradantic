<?php

declare(strict_types=1);

namespace LaraDantic\Schema\Reflection;

use ReflectionProperty;

/**
 * Describes a single typed property on a schema.
 */
final class PropertyReflection
{
    private readonly ResolvedType $type;

    private readonly bool $hasDefault;

    private readonly mixed $defaultValue;

    public function __construct(private readonly ReflectionProperty $property)
    {
        $this->property->setAccessible(true);
        $this->type = TypeResolver::resolve($property);
        $this->hasDefault = $property->hasDefaultValue();
        $this->defaultValue = $this->hasDefault ? $property->getDefaultValue() : null;
    }

    public function name(): string
    {
        return $this->property->getName();
    }

    public function type(): ResolvedType
    {
        return $this->type;
    }

    public function hasDefault(): bool
    {
        return $this->hasDefault;
    }

    public function defaultValue(): mixed
    {
        return $this->defaultValue;
    }

    public function isRequired(): bool
    {
        return ! $this->type->nullable && ! $this->hasDefault;
    }

    public function raw(): ReflectionProperty
    {
        return $this->property;
    }

    public function getValue(object $instance): mixed
    {
        return $this->property->getValue($instance);
    }

    public function setValue(object $instance, mixed $value): void
    {
        $this->property->setValue($instance, $value);
    }

    public function isInitialized(object $instance): bool
    {
        return $this->property->isInitialized($instance);
    }
}
