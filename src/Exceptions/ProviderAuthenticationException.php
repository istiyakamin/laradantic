<?php

declare(strict_types=1);

namespace LaraDantic\Exceptions;

use Illuminate\Http\Client\Response;

class ProviderAuthenticationException extends ProviderException
{
    public static function missingApiKey(string $provider): self
    {
        return new self(
            "Missing API key for AI provider [{$provider}]. Set it via ".
            "config('laradantic.providers.{$provider}.api_key') or the corresponding environment variable."
        );
    }

    public static function fromResponse(string $provider, Response $response): self
    {
        return new self(
            "Authentication failed for AI provider [{$provider}] (HTTP {$response->status()}). ".
            'Check that the configured API key is valid.'
        );
    }
}
