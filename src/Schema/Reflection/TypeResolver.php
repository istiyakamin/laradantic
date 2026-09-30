<?php

declare(strict_types=1);

namespace LaraDantic\Schema\Reflection;

use LaraDantic\Exceptions\SchemaException;
use LaraDantic\Schema\Schema;
use ReflectionNamedType;
use ReflectionProperty;

/**
 * Resolves a schema property's PHP type into a {@see ResolvedType}.
 */
final class TypeResolver
{
    private const PRIMITIVES = ['string', 'int', 'float', 'bool'];

    public static function resolve(ReflectionProperty $property): ResolvedType
    {
        $type = $property->getType();

        if ($type === null) {
            return new ResolvedType(kind: 'mixed', nullable: true);
        }

        if (! $type instanceof ReflectionNamedType) {
            throw SchemaException::unsupportedType(
                $property->getName(),
                $property->getDeclaringClass()->getName(),
                (string) $type
            );
        }

        $nullable = $type->allowsNull();
        $name = $type->getName();

        if ($name === 'mixed') {
            return new ResolvedType(kind: 'mixed', nullable: true);
        }

        if (in_array($name, self::PRIMITIVES, true)) {
            return new ResolvedType(kind: $name, nullable: $nullable);
        }

        if ($name === 'array') {
            [$itemKind, $itemClassName] = self::resolveArrayItemType($property);

            return new ResolvedType(
                kind: 'array',
                nullable: $nullable,
                itemKind: $itemKind,
                itemClassName: $itemClassName,
            );
        }

        if (! class_exists($name) && ! interface_exists($name) && ! enum_exists($name)) {
            throw SchemaException::unsupportedType(
                $property->getName(),
                $property->getDeclaringClass()->getName(),
                $name
            );
        }

        if (enum_exists($name)) {
            return new ResolvedType(kind: 'enum', nullable: $nullable, className: $name);
        }

        if (is_subclass_of($name, Schema::class)) {
            return new ResolvedType(kind: 'schema', nullable: $nullable, className: $name);
        }

        return new ResolvedType(kind: 'object', nullable: $nullable, className: $name);
    }

    /**
     * @return array{0: ?string, 1: ?string} [itemKind, itemClassName]
     */
    private static function resolveArrayItemType(ReflectionProperty $property): array
    {
        $docComment = $property->getDocComment();

        if ($docComment === false) {
            return [null, null];
        }

        if (! preg_match('/@var\s+([^\s\*]+)\[\]/', $docComment, $matches)) {
            return [null, null];
        }

        $rawType = ltrim($matches[1], '\\');

        if (in_array($rawType, self::PRIMITIVES, true)) {
            return [$rawType, null];
        }

        $declaringNamespace = $property->getDeclaringClass()->getNamespaceName();
        $candidates = [
            $rawType,
            $declaringNamespace !== '' ? $declaringNamespace.'\\'.$rawType : $rawType,
        ];

        foreach ($candidates as $candidate) {
            if (enum_exists($candidate)) {
                return ['enum', $candidate];
            }

            if (class_exists($candidate) && is_subclass_of($candidate, Schema::class)) {
                return ['schema', $candidate];
            }
        }

        return [null, null];
    }
}
