<?php

declare(strict_types=1);

namespace LaraDantic\Exceptions;

class ToolException extends LaraDanticException
{
    public static function unknownTool(string $name): self
    {
        return new self("No tool registered with name [{$name}].");
    }

    public static function notATool(string $class): self
    {
        return new self("Cannot register [{$class}] as a tool: it is not a Tool subclass.");
    }

    public static function malformedToolCall(): self
    {
        return new self('Malformed tool call: missing a function name.');
    }

    public static function notASchemaClass(string $tool, string $class): self
    {
        return new self("Tool [{$tool}]::schema() returned [{$class}], which is not a Schema subclass.");
    }

    public static function missingExecuteMethod(string $toolClass): self
    {
        return new self("Tool [{$toolClass}] does not define a public execute() method.");
    }
}
