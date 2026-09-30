<?php

declare(strict_types=1);

namespace LaraDantic\AI;

/**
 * A provider-independent AI response. Providers translate their raw payloads
 * into this shape so calling code never depends on a specific provider's format.
 */
final class AIResponse
{
    /**
     * @param  array<int, array<string, mixed>>  $toolCalls
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        private readonly ?string $text,
        private readonly array $toolCalls,
        private readonly ?string $model,
        private readonly ?string $finishReason,
        private readonly ?Usage $usage,
        private readonly array $raw,
    ) {}

    public function text(): ?string
    {
        return $this->text;
    }

    public function hasToolCalls(): bool
    {
        return $this->toolCalls !== [];
    }

    /**
     * The raw tool call payloads reported by the provider (OpenAI-compatible
     * `{id, type, function: {name, arguments}}` shape). Not yet parsed into
     * typed `ToolCall` objects — that lands with tool calling support.
     *
     * @return array<int, array<string, mixed>>
     */
    public function toolCalls(): array
    {
        return $this->toolCalls;
    }

    public function model(): ?string
    {
        return $this->model;
    }

    public function usage(): ?Usage
    {
        return $this->usage;
    }

    public function finishReason(): ?string
    {
        return $this->finishReason;
    }

    /**
     * @return array<string, mixed>
     */
    public function raw(): array
    {
        return $this->raw;
    }
}
