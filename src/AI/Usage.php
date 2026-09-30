<?php

declare(strict_types=1);

namespace LaraDantic\AI;

/**
 * Token usage for a single AI request, when the provider reports it.
 */
final class Usage
{
    public function __construct(
        private readonly ?int $inputTokens,
        private readonly ?int $outputTokens,
        private readonly ?int $totalTokens,
    ) {}

    public function inputTokens(): ?int
    {
        return $this->inputTokens;
    }

    public function outputTokens(): ?int
    {
        return $this->outputTokens;
    }

    public function totalTokens(): ?int
    {
        return $this->totalTokens;
    }
}
