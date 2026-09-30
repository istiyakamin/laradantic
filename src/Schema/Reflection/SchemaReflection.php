<?php

declare(strict_types=1);

namespace LaraDantic\Schema\Reflection;

use ReflectionClass;
use ReflectionProperty;

/**
 * Reflects a schema class into its typed properties, cached per class.
 */
final class SchemaReflection
{
    /** @var array<class-string, self> */
    private static array $cache = [];

    /** @var array<string, PropertyReflection> */
    private readonly array $properties;

    /**
     * @param  class-string  $class
     */
    private function __construct(private readonly string $class)
    {
        $reflectionClass = new ReflectionClass($class);
        $properties = [];

        foreach ($reflectionClass->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $properties[$property->getName()] = new PropertyReflection($property);
        }

        $this->properties = $properties;
    }

    /**
     * @param  class-string  $class
     */
    public static function for(string $class): self
    {
        return self::$cache[$class] ??= new self($class);
    }

    public static function clearCache(): void
    {
        self::$cache = [];
    }

    public function className(): string
    {
        return $this->class;
    }

    /**
     * @return array<string, PropertyReflection>
     */
    public function properties(): array
    {
        return $this->properties;
    }

    public function property(string $name): ?PropertyReflection
    {
        return $this->properties[$name] ?? null;
    }

    public function hasProperty(string $name): bool
    {
        return isset($this->properties[$name]);
    }
}
