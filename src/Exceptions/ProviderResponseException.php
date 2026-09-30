<?php

declare(strict_types=1);

namespace LaraDantic\Exceptions;

use Illuminate\Http\Client\Response;

class ProviderResponseException extends ProviderException
{
    public static function fromResponse(string $provider, Response $response): self
    {
        return new self(
            "AI provider [{$provider}] returned an error response (HTTP {$response->status()}): ".
            self::truncate((string) $response->body())
        );
    }

    public static function invalidStructuredOutput(string $schemaClass, string $content): self
    {
        return new self(
            "AI provider response for schema [{$schemaClass}] was not valid JSON: ".self::truncate($content)
        );
    }

    private static function truncate(string $body): string
    {
        return mb_strlen($body) > 500 ? mb_substr($body, 0, 500).'…' : $body;
    }
}
