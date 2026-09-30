<?php

declare(strict_types=1);

namespace LaraDantic\AI;

use LaraDantic\Exceptions\UnsupportedFeatureException;
use LaraDantic\Providers\AIProvider;
use LaraDantic\Providers\ProviderManager;
use LaraDantic\Schema\Schema;

/**
 * Entry point for the `AI` facade: picks a provider/model, then dispatches to
 * either a raw chat request or a fluent structured-output request.
 */
final class AIManager
{
    public function __construct(
        private readonly ProviderManager $providers,
        private readonly ?string $providerName = null,
        private readonly ?string $model = null,
    ) {}

    public function provider(string $name): self
    {
        return new self($this->providers, $name, $this->model);
    }

    public function model(string $model): self
    {
        return new self($this->providers, $this->providerName, $model);
    }

    public function extend(string $name, AIProvider $provider): void
    {
        $this->providers->extend($name, $provider);
    }

    /**
     * @param  class-string<Schema>  $schemaClass
     */
    public function structured(string $schemaClass): StructuredOutputRequest
    {
        if (! is_subclass_of($schemaClass, Schema::class)) {
            throw UnsupportedFeatureException::notASchemaClass($schemaClass);
        }

        return new StructuredOutputRequest($this->providers->driver($this->providerName), $this->model, $schemaClass);
    }

    /**
     * @param  array<int, array<string, mixed>>  $messages
     * @param  array<string, mixed>  $options
     */
    public function chat(array $messages, array $options = []): AIResponse
    {
        return $this->providers->driver($this->providerName)->chat($messages, $this->withModel($options));
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function withModel(array $options): array
    {
        if ($this->model !== null) {
            $options['model'] ??= $this->model;
        }

        return $options;
    }
}
