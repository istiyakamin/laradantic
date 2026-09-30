<?php

declare(strict_types=1);

namespace LaraDantic\Examples\BookingAgent;

use LaraDantic\Tools\Tool;
use LaraDantic\Tools\ToolResult;

/**
 * Read-only - safe to let the agent loop execute automatically.
 */
class SearchFlight extends Tool
{
    public function name(): string
    {
        return 'search_flight';
    }

    public function description(): string
    {
        return 'Search for available flights between two airports on a given date.';
    }

    public function schema(): string
    {
        return FlightSearch::class;
    }

    public function requiresConfirmation(): bool
    {
        return false;
    }

    public function execute(FlightSearch $query): ToolResult
    {
        // Real implementation: call your flight search service/API.
        return ToolResult::success([
            ['flight' => 'AB123', 'origin' => $query->origin, 'destination' => $query->destination, 'price' => 450],
        ]);
    }
}
