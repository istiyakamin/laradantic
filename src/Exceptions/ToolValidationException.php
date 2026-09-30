<?php

declare(strict_types=1);

namespace LaraDantic\Exceptions;

class ToolValidationException extends ToolException
{
    /**
     * @param  array<string, array<int, string>>  $errors
     */
    private function __construct(private readonly string $toolName, private readonly array $errors, string $message)
    {
        parent::__construct($message);
    }

    public static function invalidArguments(string $toolName, string $reason): self
    {
        return new self($toolName, [], "Invalid arguments for tool [{$toolName}]: {$reason}");
    }

    /**
     * @param  array<string, array<int, string>>  $errors
     */
    public static function fromErrors(string $toolName, array $errors): self
    {
        return new self($toolName, $errors, "Invalid arguments for tool [{$toolName}].");
    }

    public function toolName(): string
    {
        return $this->toolName;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
