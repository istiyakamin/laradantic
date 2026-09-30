<?php

declare(strict_types=1);

namespace LaraDantic\AI;

use Illuminate\Support\Collection;
use LaraDantic\Exceptions\ProviderException;
use LaraDantic\Providers\AIProvider;
use LaraDantic\Tools\ToolCall;
use LaraDantic\Tools\ToolExecutor;
use LaraDantic\Tools\ToolRegistry;

/**
 * A fluent builder for a tool-calling request: message -> provider request ->
 * parsed, validated {@see ToolCall} objects, ready for explicit execution.
 */
final class ToolCallRequest
{
    private ?string $message = null;

    private ?string $systemPrompt = null;

    /** @var array<string, mixed> */
    private array $options = [];

    public function __construct(
        private readonly AIProvider $provider,
        private readonly ?string $model,
        private readonly ToolRegistry $registry,
    ) {}

    public function message(string $message): self
    {
        $clone = clone $this;
        $clone->message = $message;

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

    /**
     * Send the request and return the tool calls the model chose to make
     * (empty if it replied with plain text instead).
     *
     * @return Collection<int, ToolCall>
     */
    public function run(): Collection
    {
        if ($this->message === null) {
            throw ProviderException::missingPrompt();
        }

        $messages = [];

        if ($this->systemPrompt !== null) {
            $messages[] = ['role' => 'system', 'content' => $this->systemPrompt];
        }

        $messages[] = ['role' => 'user', 'content' => $this->message];

        $options = $this->options;

        if ($this->model !== null) {
            $options['model'] ??= $this->model;
        }

        $response = $this->provider->tools($messages, $this->registry->definitions(), $options);

        return collect((new ToolExecutor($this->registry))->parse($response));
    }
}
