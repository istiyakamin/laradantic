<?php

declare(strict_types=1);

namespace LaraDantic\Exceptions;

use Illuminate\Http\Client\Response;

class ProviderRateLimitException extends ProviderException
{
    public static function fromResponse(string $provider, Response $response): self
    {
        return new self("AI provider [{$provider}] rate-limited the request (HTTP 429) after retries were exhausted.");
    }
}
