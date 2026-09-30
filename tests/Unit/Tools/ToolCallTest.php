<?php

declare(strict_types=1);

use LaraDantic\Tests\Fixtures\FlightBooking;
use LaraDantic\Tests\Fixtures\FlightSearchQuery;
use LaraDantic\Tests\Fixtures\Tools\CreateFlightBookingTool;
use LaraDantic\Tests\Fixtures\Tools\SearchFlightTool;
use LaraDantic\Tools\Tool;
use LaraDantic\Tools\ToolCall;
use LaraDantic\Tools\ToolResult;

function validFlightBooking(): FlightBooking
{
    return FlightBooking::from([
        'origin' => 'VIE',
        'destination' => 'DAC',
        'departureDate' => '2026-10-15',
        'returnDate' => null,
        'passengers' => 2,
        'cabin' => 'economy',
    ]);
}

it('defaults to requiring confirmation', function () {
    $toolCall = new ToolCall('call_1', new CreateFlightBookingTool, validFlightBooking());

    expect($toolCall->requiresConfirmation())->toBeTrue();
});

it('allows a tool to opt out of requiring confirmation', function () {
    $toolCall = new ToolCall('call_1', new SearchFlightTool, FlightSearchQuery::from([
        'origin' => 'VIE',
        'destination' => 'DAC',
    ]));

    expect($toolCall->requiresConfirmation())->toBeFalse();
});

it('executes the underlying tool and wraps a plain return value in a successful ToolResult', function () {
    $toolCall = new ToolCall('call_1', new CreateFlightBookingTool, validFlightBooking());

    $result = $toolCall->execute();

    expect($result)->toBeInstanceOf(ToolResult::class)
        ->and($result->successful())->toBeTrue()
        ->and($result->data()['origin'])->toBe('VIE')
        ->and($result->data()['booked_by'])->toBeNull();
});

it('forwards extra context arguments positionally to execute()', function () {
    $toolCall = new ToolCall('call_1', new CreateFlightBookingTool, validFlightBooking());

    $result = $toolCall->execute('user-42');

    expect($result->data()['booked_by'])->toBe('user-42');
});

it('catches exceptions thrown during execution and returns a failed ToolResult', function () {
    $tool = new class extends Tool
    {
        public function name(): string
        {
            return 'boom';
        }

        public function description(): string
        {
            return 'Always throws.';
        }

        public function schema(): string
        {
            return FlightBooking::class;
        }

        public function execute(FlightBooking $booking): ToolResult
        {
            throw new RuntimeException('kaboom');
        }
    };

    $toolCall = new ToolCall('call_1', $tool, validFlightBooking());
    $result = $toolCall->execute();

    expect($result->failed())->toBeTrue()
        ->and($result->errorMessage())->toBe('kaboom');
});
