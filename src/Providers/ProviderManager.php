<?php

declare(strict_types=1);

namespace LaraDantic\Providers;

use Illuminate\Contracts\Config\Repository;
use LaraDantic\Exceptions\ProviderException;
use LaraDantic\Providers\OpenRouter\OpenRouterProvider;

/**
 * Resolves configured AI providers by name, and lets applications register
 * their own {@see AIProvider} implementations.
 */
final class ProviderManager
{
    /** @var array<string, AIProvider> */
    private array $customProviders = [];

    public function __construct(private readonly Repository $config) {}

    public function driver(?string $name = null): AIProvider
    {
        $name ??= (string) $this->config->get('laradantic.default_provider', 'openrouter');

        if (isset($this->customProviders[$name])) {
            return $this->customProviders[$name];
        }

        return match ($name) {
            'openrouter' => $this->makeOpenRouter(),
            default => throw ProviderException::unknownProvider($name),
        };
    }

    public function extend(string $name, AIProvider $provider): void
    {
        $this->customProviders[$name] = $provider;
    }

    private function makeOpenRouter(): OpenRouterProvider
    {
        /** @var array<string, mixed> $config */
        $config = $this->config->get('laradantic.providers.openrouter', []);

        return new OpenRouterProvider(
            apiKey: (string) ($config['api_key'] ?? ''),
            baseUrl: (string) ($config['base_url'] ?? 'https://openrouter.ai/api/v1'),
            model: $config['model'] ?? null,
            connectTimeout: (int) $this->config->get('laradantic.connect_timeout', 10),
            requestTimeout: (int) $this->config->get('laradantic.request_timeout', 60),
            retries: (int) $this->config->get('laradantic.retries', 0),
        );
    }
}
