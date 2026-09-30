<?php

declare(strict_types=1);

namespace LaraDantic\Tools;

use LaraDantic\Exceptions\ToolException;
use LaraDantic\Schema\Schema;
use ReflectionMethod;
use Throwable;

/**
 * A single, parsed tool call: an identified {@see Tool}, and its arguments
 * already validated and hydrated into that tool's declared schema. Execution
 * is always explicit - constructing a ToolCall never runs anything.
 */
final class ToolCall
{
    public function __construct(
        private readonly string $id,
        private readonly Tool $tool,
        private readonly Schema $arguments,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->tool->name();
    }

    public function arguments(): Schema
    {
        return $this->arguments;
    }

    /**
     * @return class-string<Schema>
     */
    public function schema(): string
    {
        return $this->tool->schema();
    }

    public function requiresConfirmation(): bool
    {
        return $this->tool->requiresConfirmation();
    }

    /**
     * Explicitly execute the underlying tool. Extra arguments (e.g. an
     * authenticated User) are forwarded positionally after the arguments,
     * for tools declaring `execute(SpecificSchema $arguments, User $user)`.
     */
    public function execute(mixed ...$context): ToolResult
    {
        if (! method_exists($this->tool, 'execute')) {
            throw ToolException::missingExecuteMethod($this->tool::class);
        }

        try {
            $result = (new ReflectionMethod($this->tool, 'execute'))
                ->invoke($this->tool, $this->arguments, ...$context);
        } catch (Throwable $exception) {
            return ToolResult::error($exception);
        }

        return $result instanceof ToolResult ? $result : ToolResult::success($result);
    }
}
