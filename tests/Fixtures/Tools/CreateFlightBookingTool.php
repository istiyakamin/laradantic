<?php

declare(strict_types=1);

namespace LaraDantic\Tests\Fixtures\Tools;

use LaraDantic\Tests\Fixtures\FlightBooking;
use LaraDantic\Tools\Tool;
use LaraDantic\Tools\ToolResult;

class CreateFlightBookingTool extends Tool
{
    public function name(): string
    {
        return 'create_flight_booking';
    }

    public function description(): string
    {
        return 'Create a flight booking request.';
    }

    public function schema(): string
    {
        return FlightBooking::class;
    }

    public function execute(FlightBooking $booking, ?string $userId = null): ToolResult
    {
        return ToolResult::success([
            'id' => 'BK-123',
            'origin' => $booking->origin,
            'destination' => $booking->destination,
            'booked_by' => $userId,
        ]);
    }
}
