<?php

declare(strict_types=1);

namespace LaraDantic\Tools;

use LaraDantic\Exceptions\ToolException;
use LaraDantic\Schema\Schema;

/**
 * Base class for AI-callable tools. A concrete tool declares its own
 * `execute(SpecificSchema $arguments, mixed ...$context): ToolResult` method
 * (deliberately not part of this abstract contract, since PHP's parameter
 * variance rules forbid narrowing an abstract `Schema $arguments` parameter
 * to a concrete schema type in the subclass) - {@see ToolCall::execute()}
 * calls it dynamically once arguments have been validated and hydrated.
 */
abstract class Tool
{
    abstract public function name(): string;

    abstract public function description(): string;

    /**
     * @return class-string<Schema>
     */
    abstract public function schema(): string;

    /**
     * Whether the host application should ask for explicit confirmation
     * before calling {@see ToolCall::execute()}. Defaults to true (safe by
     * default) - override to false for genuinely read-only/query tools.
     */
    public function requiresConfirmation(): bool
    {
        return true;
    }

    /**
     * The OpenAI/OpenRouter-compatible function-calling definition for this tool.
     *
     * @return array<string, mixed>
     */
    public function toDefinition(): array
    {
        $schemaClass = $this->schema();

        if (! is_subclass_of($schemaClass, Schema::class)) {
            throw ToolException::notASchemaClass($this->name(), $schemaClass);
        }

        return [
            'type' => 'function',
            'function' => [
                'name' => $this->name(),
                'description' => $this->description(),
                'parameters' => $schemaClass::jsonSchema(),
            ],
        ];
    }
}
