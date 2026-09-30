<?php

declare(strict_types=1);

namespace LaraDantic\Providers\OpenRouter;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use LaraDantic\AI\AIResponse;
use LaraDantic\Exceptions\ProviderAuthenticationException;
use LaraDantic\Exceptions\ProviderException;
use LaraDantic\Exceptions\ProviderRateLimitException;
use LaraDantic\Exceptions\ProviderResponseException;
use LaraDantic\Providers\AIProvider;
use Throwable;

final class OpenRouterProvider implements AIProvider
{
    private const NAME = 'openrouter';

    private const RETRYABLE_STATUSES = [429, 500, 502, 503, 504];

    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly ?string $model,
        private readonly int $connectTimeout = 10,
        private readonly int $requestTimeout = 60,
        private readonly int $retries = 0,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $messages
     * @param  array<string, mixed>  $options
     */
    public function chat(array $messages, array $options = []): AIResponse
    {
        return $this->request(array_merge(['messages' => $messages], $options));
    }

    /**
     * @param  array<int, array<string, mixed>>  $messages
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $options
     */
    public function structured(array $messages, array $schema, array $options = []): AIResponse
    {
        $schemaName = $options['schema_name'] ?? 'structured_output';
        unset($options['schema_name']);

        $payload = array_merge([
            'messages' => $messages,
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => $schemaName,
                    'strict' => false,
                    'schema' => $schema,
                ],
            ],
        ], $options);

        return $this->request($payload);
    }

    /**
     * @param  array<int, array<string, mixed>>  $messages
     * @param  array<int, array<string, mixed>>  $tools
     * @param  array<string, mixed>  $options
     */
    public function tools(array $messages, array $tools, array $options = []): AIResponse
    {
        return $this->request(array_merge(['messages' => $messages, 'tools' => $tools], $options));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function request(array $payload): AIResponse
    {
        if ($this->apiKey === '') {
            throw ProviderAuthenticationException::missingApiKey(self::NAME);
        }

        $payload['model'] ??= $this->model ?? throw ProviderException::missingModel(self::NAME);

        $tries = max(1, $this->retries + 1);

        $response = Http::baseUrl($this->baseUrl)
            ->withToken($this->apiKey)
            ->connectTimeout($this->connectTimeout)
            ->timeout($this->requestTimeout)
            ->retry($tries, 250, $this->retryWhen(...), throw: false)
            ->post('/chat/completions', $payload);

        return $this->handleResponse($response);
    }

    private function retryWhen(Throwable $exception): bool
    {
        if ($exception instanceof ConnectionException) {
            return true;
        }

        if ($exception instanceof RequestException) {
            return in_array($exception->response->status(), self::RETRYABLE_STATUSES, true);
        }

        return false;
    }

    private function handleResponse(Response $response): AIResponse
    {
        if (in_array($response->status(), [401, 403], true)) {
            throw ProviderAuthenticationException::fromResponse(self::NAME, $response);
        }

        if ($response->status() === 429) {
            throw ProviderRateLimitException::fromResponse(self::NAME, $response);
        }

        if ($response->failed()) {
            throw ProviderResponseException::fromResponse(self::NAME, $response);
        }

        /** @var array<string, mixed> $decoded */
        $decoded = $response->json() ?? [];

        return OpenRouterResponse::fromArray($decoded)->toAIResponse();
    }
}
