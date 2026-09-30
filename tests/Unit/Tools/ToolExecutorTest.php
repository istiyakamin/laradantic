<?php

declare(strict_types=1);

use LaraDantic\AI\AIResponse;
use LaraDantic\Exceptions\ToolException;
use LaraDantic\Exceptions\ToolValidationException;
use LaraDantic\Tests\Fixtures\FlightSearchQuery;
use LaraDantic\Tests\Fixtures\Tools\SearchFlightTool;
use LaraDantic\Tools\ToolCall;
use LaraDantic\Tools\ToolExecutor;
use LaraDantic\Tools\ToolRegistry;

function aiResponseWithToolCalls(array $toolCalls): AIResponse
{
    return new AIResponse(null, $toolCalls, 'test-model', 'tool_calls', null, []);
}

function registryWithSearchFlight(): ToolRegistry
{
    return (new ToolRegistry)->register(SearchFlightTool::class);
}

it('parses a raw tool call into a validated, hydrated ToolCall', function () {
    $response = aiResponseWithToolCalls([[
        'id' => 'call_1',
        'type' => 'function',
        'function' => [
            'name' => 'search_flight',
            'arguments' => json_encode(['origin' => 'VIE', 'destination' => 'DAC']),
        ],
    ]]);

    $toolCalls = (new ToolExecutor(registryWithSearchFlight()))->parse($response);

    expect($toolCalls)->toHaveCount(1)
        ->and($toolCalls[0])->toBeInstanceOf(ToolCall::class)
        ->and($toolCalls[0]->id())->toBe('call_1')
        ->and($toolCalls[0]->name())->toBe('search_flight')
        ->and($toolCalls[0]->arguments())->toBeInstanceOf(FlightSearchQuery::class)
        ->and($toolCalls[0]->arguments()->origin)->toBe('VIE')
        ->and($toolCalls[0]->requiresConfirmation())->toBeFalse();
});

it('accepts already-decoded array arguments, not just a JSON string', function () {
    $response = aiResponseWithToolCalls([[
        'id' => 'call_1',
        'function' => ['name' => 'search_flight', 'arguments' => ['origin' => 'VIE', 'destination' => 'DAC']],
    ]]);

    $toolCalls = (new ToolExecutor(registryWithSearchFlight()))->parse($response);

    expect($toolCalls[0]->arguments()->origin)->toBe('VIE');
});

it('throws ToolException for an unknown tool name', function () {
    $response = aiResponseWithToolCalls([[
        'id' => 'call_1',
        'function' => ['name' => 'does_not_exist', 'arguments' => '{}'],
    ]]);

    (new ToolExecutor(registryWithSearchFlight()))->parse($response);
})->throws(ToolException::class);

it('throws ToolException for a malformed tool call with no function name', function () {
    $response = aiResponseWithToolCalls([[
        'id' => 'call_1',
        'function' => ['arguments' => '{}'],
    ]]);

    (new ToolExecutor(registryWithSearchFlight()))->parse($response);
})->throws(ToolException::class);

it('throws ToolValidationException with structured errors when arguments fail schema validation', function () {
    $response = aiResponseWithToolCalls([[
        'id' => 'call_1',
        'function' => ['name' => 'search_flight', 'arguments' => json_encode(['origin' => 'VIE'])],
    ]]);

    try {
        (new ToolExecutor(registryWithSearchFlight()))->parse($response);
        $this->fail('Expected ToolValidationException was not thrown.');
    } catch (ToolValidationException $exception) {
        expect($exception->toolName())->toBe('search_flight')
            ->and($exception->errors())->toHaveKey('destination');
    }
});
