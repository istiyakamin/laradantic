<?php

declare(strict_types=1);

namespace LaraDantic\Validation;

/**
 * The outcome of validating raw data against a schema's rules.
 */
final class ValidationResult
{
    /**
     * @param  array<string, array<int, string>>  $errors
     */
    private function __construct(
        private readonly bool $valid,
        private readonly array $errors,
    ) {}

    public static function success(): self
    {
        return new self(true, []);
    }

    /**
     * @param  array<string, array<int, string>>  $errors
     */
    public static function failure(array $errors): self
    {
        return new self(false, $errors);
    }

    public function passes(): bool
    {
        return $this->valid;
    }

    public function fails(): bool
    {
        return ! $this->valid;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
