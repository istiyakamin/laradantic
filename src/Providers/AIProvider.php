<?php

declare(strict_types=1);

namespace LaraDantic\Providers;

use LaraDantic\AI\AIResponse;

/**
 * The contract every AI provider (OpenRouter, and future providers) must implement.
 * Keeping this provider-neutral is what lets LaraDantic swap providers without
 * touching the schema or AI layers.
 */
interface AIProvider
{
    /**
     * @param  array<int, array<string, mixed>>  $messages
     * @param  array<string, mixed>  $options
     */
    public function chat(array $messages, array $options = []): AIResponse;

    /**
     * @param  array<int, array<string, mixed>>  $messages
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $options
     */
    public function structured(array $messages, array $schema, array $options = []): AIResponse;

    /**
     * @param  array<int, array<string, mixed>>  $messages
     * @param  array<int, array<string, mixed>>  $tools
     * @param  array<string, mixed>  $options
     */
    public function tools(array $messages, array $tools, array $options = []): AIResponse;
}
