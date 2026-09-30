<?php

declare(strict_types=1);

namespace LaraDantic\Schema\Reflection;

/**
 * Describes the resolved type of a schema property.
 */
final class ResolvedType
{
    public function __construct(
        public readonly string $kind,
        public readonly bool $nullable,
        public readonly ?string $className = null,
        public readonly ?string $itemKind = null,
        public readonly ?string $itemClassName = null,
    ) {}

    public function isPrimitive(): bool
    {
        return in_array($this->kind, ['string', 'int', 'float', 'bool'], true);
    }

    public function isEnum(): bool
    {
        return $this->kind === 'enum';
    }

    public function isSchema(): bool
    {
        return $this->kind === 'schema';
    }

    public function isArray(): bool
    {
        return $this->kind === 'array';
    }

    public function isMixed(): bool
    {
        return $this->kind === 'mixed';
    }

    public function hasTypedItems(): bool
    {
        return $this->isArray() && $this->itemKind !== null;
    }
}
