<?php

declare(strict_types=1);

namespace LaraDantic\Exceptions;

use LaraDantic\Validation\ValidationResult;

class SchemaValidationException extends LaraDanticException
{
    /**
     * @param  array<string, array<int, string>>  $errors
     */
    public function __construct(private readonly array $errors, string $message = 'The given data was invalid.')
    {
        parent::__construct($message);
    }

    public static function fromResult(ValidationResult $result): self
    {
        return new self($result->errors());
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
