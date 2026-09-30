<?php

declare(strict_types=1);

namespace LaraDantic\Schema;

use BackedEnum;
use LaraDantic\Exceptions\SchemaException;
use LaraDantic\Schema\Reflection\SchemaReflection;
use UnitEnum;

/**
 * Serializes a schema instance back into a plain array.
 */
final class SchemaSerializer
{
    /**
     * @return array<string, mixed>
     */
    public static function serialize(Schema $schema): array
    {
        $reflection = SchemaReflection::for($schema::class);
        $result = [];

        foreach ($reflection->properties() as $property) {
            if (! $property->isInitialized($schema)) {
                if ($property->type()->nullable) {
                    $result[$property->name()] = null;

                    continue;
                }

                throw SchemaException::uninitializedProperty($property->name(), $schema::class);
            }

            $result[$property->name()] = self::serializeValue($property->getValue($schema));
        }

        return $result;
    }

    private static function serializeValue(mixed $value): mixed
    {
        return match (true) {
            $value instanceof Schema => $value->toArray(),
            $value instanceof BackedEnum => $value->value,
            $value instanceof UnitEnum => $value->name,
            is_array($value) => array_map(self::serializeValue(...), $value),
            default => $value,
        };
    }
}
