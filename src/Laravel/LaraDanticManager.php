<?php

declare(strict_types=1);

namespace LaraDantic\Laravel;

/**
 * Container-bound entry point for the `LaraDantic` facade.
 *
 * This is intentionally minimal for now — it will grow into the AI manager
 * (structured output, tools, agents) in later milestones.
 */
class LaraDanticManager
{
    public function version(): string
    {
        return '0.1.0';
    }
}
