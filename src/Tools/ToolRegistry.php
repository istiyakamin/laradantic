<?php

declare(strict_types=1);

namespace LaraDantic\Tools;

use LaraDantic\Exceptions\ToolException;

/**
 * Holds the set of tools available for an AI request, keyed by tool name.
 */
final class ToolRegistry
{
    /** @var array<string, Tool> */
    private array $tools = [];

    /**
     * @param  class-string<Tool>|Tool  $tool
     */
    public function register(string|Tool $tool): self
    {
        $instance = is_string($tool) ? self::instantiate($tool) : $tool;

        $this->tools[$instance->name()] = $instance;

        return $this;
    }

    public function has(string $name): bool
    {
        return isset($this->tools[$name]);
    }

    public function get(string $name): Tool
    {
        return $this->tools[$name] ?? throw ToolException::unknownTool($name);
    }

    /**
     * @return array<int, Tool>
     */
    public function all(): array
    {
        return array_values($this->tools);
    }

    /**
     * The OpenAI/OpenRouter-compatible tool definitions for every registered tool.
     *
     * @return array<int, array<string, mixed>>
     */
    public function definitions(): array
    {
        return array_map(static fn (Tool $tool): array => $tool->toDefinition(), $this->all());
    }

    private static function instantiate(string $class): Tool
    {
        if (! is_subclass_of($class, Tool::class)) {
            throw ToolException::notATool($class);
        }

        return new $class;
    }
}
