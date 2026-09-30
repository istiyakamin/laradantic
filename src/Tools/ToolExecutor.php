<?php

declare(strict_types=1);

namespace LaraDantic\Tools;

use LaraDantic\AI\AIResponse;
use LaraDantic\Exceptions\SchemaValidationException;
use LaraDantic\Exceptions\ToolException;
use LaraDantic\Exceptions\ToolValidationException;
use LaraDantic\Schema\Schema;

/**
 * Parses an AI response's raw tool call payloads into typed, validated {@see ToolCall} objects:
 * identify the tool, decode arguments, validate them, hydrate the tool's schema.
 */
final class ToolExecutor
{
    public function __construct(private readonly ToolRegistry $registry) {}

    /**
     * @return array<int, ToolCall>
     */
    public function parse(AIResponse $response): array
    {
        return array_map(
            fn (array $raw): ToolCall => $this->parseOne($raw),
            $response->toolCalls()
        );
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    private function parseOne(array $raw): ToolCall
    {
        /** @var array<string, mixed> $function */
        $function = $raw['function'] ?? [];
        $name = $function['name'] ?? null;

        if (! is_string($name) || $name === '') {
            throw ToolException::malformedToolCall();
        }

        $tool = $this->registry->get($name);

        $rawArguments = $function['arguments'] ?? '{}';
        $decoded = is_string($rawArguments) ? json_decode($rawArguments, true) : $rawArguments;

        if (! is_array($decoded)) {
            throw ToolValidationException::invalidArguments($name, 'arguments were not valid JSON');
        }

        /** @var array<string, mixed> $decoded */
        $schemaClass = $tool->schema();

        if (! is_subclass_of($schemaClass, Schema::class)) {
            throw ToolException::notASchemaClass($name, $schemaClass);
        }

        try {
            $arguments = $schemaClass::validated($decoded);
        } catch (SchemaValidationException $exception) {
            throw ToolValidationException::fromErrors($name, $exception->errors());
        }

        $id = $raw['id'] ?? '';

        return new ToolCall(is_string($id) ? $id : '', $tool, $arguments);
    }
}
