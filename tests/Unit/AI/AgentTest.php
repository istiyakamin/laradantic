<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use LaraDantic\AI\AgentStatus;
use LaraDantic\Tests\Fixtures\Tools\CreateFlightBookingTool;
use LaraDantic\Tests\Fixtures\Tools\SearchFlightTool;

beforeEach(function () {
    config([
        'laradantic.providers.openrouter.api_key' => 'test-key',
        'laradantic.providers.openrouter.model' => 'openrouter/test-model',
        'laradantic.retries' => 0,
    ]);
});

function agentTextResponse(string $text): array
{
    return [
        'model' => 'openrouter/test-model',
        'choices' => [[
            'index' => 0,
            'message' => ['role' => 'assistant', 'content' => $text],
            'finish_reason' => 'stop',
        ]],
    ];
}

function agentToolCallResponse(string $callId, string $name, array $arguments): array
{
    return [
        'model' => 'openrouter/test-model',
        'choices' => [[
            'index' => 0,
            'message' => [
                'role' => 'assistant',
                'content' => null,
                'tool_calls' => [[
                    'id' => $callId,
                    'type' => 'function',
                    'function' => ['name' => $name, 'arguments' => json_encode($arguments)],
                ]],
            ],
            'finish_reason' => 'tool_calls',
        ]],
    ];
}

it('completes immediately when the model replies with plain text', function () {
    Http::fake(['*/chat/completions' => Http::response(agentTextResponse('Hello, how can I help?'))]);

    $result = app('laradantic.ai')->agent()->tools([SearchFlightTool::class])->run('Hi');

    expect($result->completed())->toBeTrue()
        ->and($result->text())->toBe('Hello, how can I help?')
        ->and($result->iterations())->toBe(1);

    Http::assertSentCount(1);
});

it('auto-executes a tool that does not require confirmation and feeds the result back to the model', function () {
    Http::fake(['*/chat/completions' => Http::sequence()
        ->push(agentToolCallResponse('call_1', 'search_flight', ['origin' => 'VIE', 'destination' => 'DAC']))
        ->push(agentTextResponse('Found flight AB123.'))]);

    $result = app('laradantic.ai')->agent()->tools([SearchFlightTool::class])->run('Find me a flight');

    expect($result->completed())->toBeTrue()
        ->and($result->text())->toBe('Found flight AB123.')
        ->and($result->iterations())->toBe(2)
        ->and($result->executions())->toHaveCount(1)
        ->and($result->executions()[0]['result']->successful())->toBeTrue();

    Http::assertSentCount(2);

    Http::assertSent(function ($request) {
        $messages = $request['messages'];

        return collect($messages)->contains(fn ($m) => ($m['role'] ?? null) === 'tool');
    });
});

it('stops without executing when a tool requires confirmation', function () {
    Http::fake(['*/chat/completions' => Http::response(
        agentToolCallResponse('call_1', 'create_flight_booking', [
            'origin' => 'VIE', 'destination' => 'DAC', 'departureDate' => '2026-10-15',
            'returnDate' => null, 'passengers' => 2, 'cabin' => 'economy',
        ])
    )]);

    $result = app('laradantic.ai')->agent()->tools([CreateFlightBookingTool::class])->run('Book it');

    expect($result->status())->toBe(AgentStatus::PendingConfirmation)
        ->and($result->pendingToolCalls())->toHaveCount(1)
        ->and($result->pendingToolCalls()[0]->name())->toBe('create_flight_booking')
        ->and($result->executions())->toBe([]);

    Http::assertSentCount(1);
});

it('resumes after confirmation and completes', function () {
    Http::fake(['*/chat/completions' => Http::sequence()
        ->push(agentToolCallResponse('call_1', 'create_flight_booking', [
            'origin' => 'VIE', 'destination' => 'DAC', 'departureDate' => '2026-10-15',
            'returnDate' => null, 'passengers' => 2, 'cabin' => 'economy',
        ]))
        ->push(agentTextResponse('Booking confirmed.'))]);

    $agent = app('laradantic.ai')->agent()->tools([CreateFlightBookingTool::class]);
    $paused = $agent->run('Book it');

    $toolCall = $paused->pendingToolCalls()[0];
    $result = $toolCall->execute(); // host confirmed, executes explicitly

    $resumed = $agent->resume($paused->messages(), [['toolCall' => $toolCall, 'result' => $result]]);

    expect($resumed->completed())->toBeTrue()
        ->and($resumed->text())->toBe('Booking confirmed.');

    Http::assertSentCount(2);
});

it('stops at maxIterations instead of looping forever', function () {
    Http::fake(['*/chat/completions' => Http::response(
        agentToolCallResponse('call_1', 'search_flight', ['origin' => 'VIE', 'destination' => 'DAC'])
    )]);

    $result = app('laradantic.ai')->agent()
        ->tools([SearchFlightTool::class])
        ->maxIterations(2)
        ->run('Find me a flight');

    expect($result->maxIterationsReached())->toBeTrue()
        ->and($result->iterations())->toBe(2);

    Http::assertSentCount(2);
});

it('forwards extra context arguments to auto-executed tools', function () {
    Http::fake(['*/chat/completions' => Http::sequence()
        ->push(agentToolCallResponse('call_1', 'search_flight', ['origin' => 'VIE', 'destination' => 'DAC']))
        ->push(agentTextResponse('Done.'))]);

    $result = app('laradantic.ai')->agent()->tools([SearchFlightTool::class])->run('Find a flight', 'user-42');

    expect($result->executions()[0]['result']->data()['requester'])->toBe('user-42');
});
