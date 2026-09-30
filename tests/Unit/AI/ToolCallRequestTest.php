<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use LaraDantic\Exceptions\ProviderException;
use LaraDantic\Tests\Fixtures\Tools\CreateFlightBookingTool;
use LaraDantic\Tests\Fixtures\Tools\SearchFlightTool;
use LaraDantic\Tools\ToolCall;

beforeEach(function () {
    config([
        'laradantic.providers.openrouter.api_key' => 'test-key',
        'laradantic.providers.openrouter.model' => 'openrouter/test-model',
        'laradantic.retries' => 0,
    ]);
});

function toolCallCompletionResponse(string $name, array $arguments): array
{
    return [
        'model' => 'openrouter/test-model',
        'choices' => [[
            'index' => 0,
            'message' => [
                'role' => 'assistant',
                'content' => null,
                'tool_calls' => [[
                    'id' => 'call_1',
                    'type' => 'function',
                    'function' => ['name' => $name, 'arguments' => json_encode($arguments)],
                ]],
            ],
            'finish_reason' => 'tool_calls',
        ]],
    ];
}

it('runs a tool-calling request end-to-end into typed, executable ToolCall objects', function () {
    Http::fake(['*/chat/completions' => Http::response(
        toolCallCompletionResponse('search_flight', ['origin' => 'VIE', 'destination' => 'DAC'])
    )]);

    $toolCalls = app('laradantic.ai')
        ->tools([CreateFlightBookingTool::class, SearchFlightTool::class])
        ->message('Find me a flight from Vienna to Dhaka')
        ->run();

    expect($toolCalls)->toBeInstanceOf(Collection::class)
        ->and($toolCalls)->toHaveCount(1);

    /** @var ToolCall $toolCall */
    $toolCall = $toolCalls->first();

    expect($toolCall->name())->toBe('search_flight')
        ->and($toolCall->requiresConfirmation())->toBeFalse()
        ->and($toolCall->arguments()->origin)->toBe('VIE');

    $result = $toolCall->execute();
    expect($result->successful())->toBeTrue();

    Http::assertSent(fn ($request) => isset($request['tools']) && $request['tools'][0]['function']['name'] === 'create_flight_booking');
});

it('returns an empty collection when the model replies with plain text instead of a tool call', function () {
    Http::fake(['*/chat/completions' => Http::response([
        'model' => 'openrouter/test-model',
        'choices' => [[
            'index' => 0,
            'message' => ['role' => 'assistant', 'content' => 'I need more information.'],
            'finish_reason' => 'stop',
        ]],
    ])]);

    $toolCalls = app('laradantic.ai')->tools([SearchFlightTool::class])->message('Hi')->run();

    expect($toolCalls)->toBeInstanceOf(Collection::class)->and($toolCalls)->toBeEmpty();
});

it('throws when run() is called without a message', function () {
    app('laradantic.ai')->tools([SearchFlightTool::class])->run();
})->throws(ProviderException::class);
