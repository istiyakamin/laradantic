<?php

declare(strict_types=1);

namespace LaraDantic\Examples\BookingAgent;

use LaraDantic\Tools\Tool;
use LaraDantic\Tools\ToolResult;

/**
 * Has a real side effect (creates a booking, presumably charges a card
 * eventually) - requiresConfirmation() is left at Tool's default of
 * `true`, so the agent loop will never execute it without the host
 * application confirming first. See docs/security.md.
 */
class CreateFlightBooking extends Tool
{
    public function name(): string
    {
        return 'create_flight_booking';
    }

    public function description(): string
    {
        return 'Create a flight booking request for the given itinerary.';
    }

    public function schema(): string
    {
        return FlightBooking::class;
    }

    public function execute(FlightBooking $booking): ToolResult
    {
        // Real implementation: hand off to your CRM/booking system.
        // $user would come from your application (e.g. auth()->user()),
        // never from the model - see docs/security.md.
        return ToolResult::success([
            'id' => 'BK-'.strtoupper(substr(md5($booking->origin.$booking->destination.$booking->departureDate), 0, 6)),
            'origin' => $booking->origin,
            'destination' => $booking->destination,
        ]);
    }
}
