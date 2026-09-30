<?php

declare(strict_types=1);

namespace LaraDantic\Tools;

use BackedEnum;
use LaraDantic\Schema\Schema;
use Throwable;
use UnitEnum;

/**
 * The outcome of executing a tool call, serializable back into an AI conversation.
 */
final class ToolResult
{
    private function __construct(
        private readonly bool $successful,
        private readonly mixed $data,
        private readonly ?string $errorMessage,
    ) {}

    public static function success(mixed $data = null): self
    {
        return new self(true, $data, null);
    }

    public static function failure(string $message): self
    {
        return new self(false, null, $message);
    }

    public static function error(Throwable $exception): self
    {
        return new self(false, null, $exception->getMessage());
    }

    public function successful(): bool
    {
        return $this->successful;
    }

    public function failed(): bool
    {
        return ! $this->successful;
    }

    public function data(): mixed
    {
        return $this->data;
    }

    public function errorMessage(): ?string
    {
        return $this->errorMessage;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => $this->successful,
            'data' => self::normalize($this->data),
            'error' => $this->errorMessage,
        ];
    }

    private static function normalize(mixed $value): mixed
    {
        return match (true) {
            $value instanceof Schema => $value->toArray(),
            $value instanceof BackedEnum => $value->value,
            $value instanceof UnitEnum => $value->name,
            is_array($value) => array_map(self::normalize(...), $value),
            default => $value,
        };
    }
}
