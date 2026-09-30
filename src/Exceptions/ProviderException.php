<?php

declare(strict_types=1);

namespace LaraDantic\Exceptions;

class ProviderException extends LaraDanticException
{
    public static function missingModel(string $provider): self
    {
        return new self(
            "No model specified for AI provider [{$provider}]. Pass one explicitly via ->model() ".
            "or set a default in config('laradantic.providers.{$provider}.model')."
        );
    }

    public static function missingPrompt(): self
    {
        return new self('A prompt is required. Call ->prompt() before ->run().');
    }

    public static function unknownProvider(string $name): self
    {
        return new self("Unknown AI provider [{$name}]. Register it first via the provider manager's extend() method.");
    }
}
