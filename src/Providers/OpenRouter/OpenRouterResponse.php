<?php

declare(strict_types=1);

namespace LaraDantic\Providers\OpenRouter;

use LaraDantic\AI\AIResponse;
use LaraDantic\AI\Usage;

/**
 * Translates a raw, decoded OpenRouter (OpenAI-compatible) chat completion
 * payload into the provider-neutral {@see AIResponse}.
 */
final class OpenRouterResponse
{
    /**
     * @param  array<string, mixed>  $raw
     */
    private function __construct(private readonly array $raw) {}

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromArray(array $raw): self
    {
        return new self($raw);
    }

    public function toAIResponse(): AIResponse
    {
        /** @var array<string, mixed> $choice */
        $choice = $this->raw['choices'][0] ?? [];
        /** @var array<string, mixed> $message */
        $message = $choice['message'] ?? [];
        /** @var array<string, mixed>|null $usage */
        $usage = $this->raw['usage'] ?? null;

        /** @var array<int, array<string, mixed>> $toolCalls */
        $toolCalls = $message['tool_calls'] ?? [];

        return new AIResponse(
            text: isset($message['content']) ? (string) $message['content'] : null,
            toolCalls: $toolCalls,
            model: isset($this->raw['model']) ? (string) $this->raw['model'] : null,
            finishReason: isset($choice['finish_reason']) ? (string) $choice['finish_reason'] : null,
            usage: $usage !== null ? new Usage(
                inputTokens: isset($usage['prompt_tokens']) ? (int) $usage['prompt_tokens'] : null,
                outputTokens: isset($usage['completion_tokens']) ? (int) $usage['completion_tokens'] : null,
                totalTokens: isset($usage['total_tokens']) ? (int) $usage['total_tokens'] : null,
            ) : null,
            raw: $this->raw,
        );
    }
}
