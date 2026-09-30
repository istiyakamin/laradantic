<?php

declare(strict_types=1);

/**
 * Example: a booking assistant agent.
 *
 *   User -> AI -> FlightBooking schema -> validation -> tool call -> confirmation -> "CRM"
 *
 * Demonstrates the full agent loop, including the confirmation boundary:
 * the read-only SearchFlight tool runs automatically; CreateFlightBooking
 * (a real side effect) pauses the loop instead, and is only executed
 * after this script explicitly "confirms" it - standing in for whatever
 * your application's actual confirmation UI/flow looks like.
 *
 * Run from within a booted Laravel app. The two chat completion responses
 * are faked here so this runs without a real OPENROUTER_API_KEY; drop the
 * Http::fake() call to run it against the real OpenRouter API instead.
 */

require __DIR__.'/Cabin.php';
require __DIR__.'/FlightBooking.php';
require __DIR__.'/FlightSearch.php';
require __DIR__.'/SearchFlight.php';
require __DIR__.'/CreateFlightBooking.php';

use Illuminate\Support\Facades\Http;
use LaraDantic\AI\AgentStatus;
use LaraDantic\Examples\BookingAgent\CreateFlightBooking;
use LaraDantic\Examples\BookingAgent\SearchFlight;

Http::fake(['*/chat/completions' => Http::sequence()
    // Turn 1: the model searches for flights first (auto-executed - read-only).
    ->push([
        'model' => 'openrouter/some-model',
        'choices' => [['message' => [
            'role' => 'assistant',
            'content' => null,
            'tool_calls' => [[
                'id' => 'call_1',
                'type' => 'function',
                'function' => ['name' => 'search_flight', 'arguments' => json_encode([
                    'origin' => 'VIE', 'destination' => 'DAC', 'departureDate' => '2026-10-15',
                ])],
            ]],
        ], 'finish_reason' => 'tool_calls']],
    ])
    // Turn 2: having seen the search result, the model asks to create the booking.
    ->push([
        'model' => 'openrouter/some-model',
        'choices' => [['message' => [
            'role' => 'assistant',
            'content' => null,
            'tool_calls' => [[
                'id' => 'call_2',
                'type' => 'function',
                'function' => ['name' => 'create_flight_booking', 'arguments' => json_encode([
                    'origin' => 'VIE', 'destination' => 'DAC', 'departureDate' => '2026-10-15',
                    'returnDate' => null, 'passengers' => 2, 'cabin' => 'business',
                ])],
            ]],
        ], 'finish_reason' => 'tool_calls']],
    ])
    // Turn 3 (after resume()): the booking is confirmed, model gives a final reply.
    ->push([
        'model' => 'openrouter/some-model',
        'choices' => [['message' => [
            'role' => 'assistant',
            'content' => 'Booked! Your flight AB123 from VIE to DAC on 2026-10-15 is confirmed.',
        ], 'finish_reason' => 'stop']],
    ])]);

$agent = AI::agent()
    ->tools([SearchFlight::class, CreateFlightBooking::class])
    ->system('You are a travel booking assistant.')
    ->maxIterations(5);

$result = $agent->run('Book me a flight from Vienna to Dhaka on 2026-10-15 for 2 people, business class.');

if ($result->status() === AgentStatus::PendingConfirmation) {
    $pending = $result->pendingToolCalls()[0];

    echo "Confirm before proceeding: {$pending->name()}(".json_encode($pending->arguments()->toArray()).')'.PHP_EOL;

    // Stand-in for a real confirmation UI/flow - the application decides this, not the model.
    $confirmed = true;

    if ($confirmed) {
        $toolResult = $pending->execute();

        $result = $agent->resume($result->messages(), [
            ['toolCall' => $pending, 'result' => $toolResult],
        ]);
    }
}

echo $result->text().PHP_EOL;
