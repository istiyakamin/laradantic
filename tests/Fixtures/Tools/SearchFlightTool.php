<?php

declare(strict_types=1);

namespace LaraDantic\Tests\Fixtures\Tools;

use LaraDantic\Tests\Fixtures\FlightSearchQuery;
use LaraDantic\Tools\Tool;
use LaraDantic\Tools\ToolResult;

class SearchFlightTool extends Tool
{
    public function name(): string
    {
        return 'search_flight';
    }

    public function description(): string
    {
        return 'Search for available flights.';
    }

    public function schema(): string
    {
        return FlightSearchQuery::class;
    }

    public function requiresConfirmation(): bool
    {
        return false;
    }

    public function execute(FlightSearchQuery $query, ?string $requesterId = null): ToolResult
    {
        return ToolResult::success([
            'requester' => $requesterId,
            'flights' => [
                ['flight' => 'AB123', 'origin' => $query->origin, 'destination' => $query->destination],
            ],
        ]);
    }
}
