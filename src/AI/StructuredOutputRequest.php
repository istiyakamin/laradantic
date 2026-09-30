<?php

declare(strict_types=1);

namespace LaraDantic\AI;

use LaraDantic\Exceptions\ProviderException;
use LaraDantic\Exceptions\ProviderResponseException;
use LaraDantic\Providers\AIProvider;
use LaraDantic\Schema\Schema;

/**
 * A fluent builder for requesting structured AI output that hydrates into a
 * {@see Schema} instance: prompt -> provider request -> JSON decode -> validate -> typed object.
 */
final class StructuredOutputRequest
{
    private ?string $prompt = null;

    private ?string $systemPrompt = null;

    /** @var array<string, mixed> */
    private array $options = [];

    /**
     * @param  class-string<Schema>  $schemaClass
     */
    public function __construct(
        private readonly AIProvider $provider,
        private readonly ?string $model,
        private readonly string $schemaClass,
    ) {}

    public function prompt(string $prompt): self
    {
        $clone = clone $this;
        $clone->prompt = $prompt;

        return $clone;
    }

    public function system(string $prompt): self
    {
        $clone = clone $this;
        $clone->systemPrompt = $prompt;

        return $clone;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function options(array $options): self
    {
        $clone = clone $this;
        $clone->options = array_merge($this->options, $options);

        return $clone;
    }

    public function run(): Schema
    {
        if ($this->prompt === null) {
            throw ProviderException::missingPrompt();
        }

        $messages = [];

        if ($this->systemPrompt !== null) {
            $messages[] = ['role' => 'system', 'content' => $this->systemPrompt];
        }

        $messages[] = ['role' => 'user', 'content' => $this->prompt];

        $options = $this->options;

        if ($this->model !== null) {
            $options['model'] ??= $this->model;
        }

        $options['schema_name'] ??= class_basename($this->schemaClass);

        $schemaClass = $this->schemaClass;

        $response = $this->provider->structured($messages, $schemaClass::jsonSchema(), $options);

        $decoded = json_decode((string) $response->text(), true);

        if (! is_array($decoded)) {
            throw ProviderResponseException::invalidStructuredOutput($schemaClass, (string) $response->text());
        }

        /** @var array<string, mixed> $decoded */
        return $schemaClass::validated($decoded);
    }
}
