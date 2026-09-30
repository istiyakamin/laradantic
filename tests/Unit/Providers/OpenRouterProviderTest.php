<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use LaraDantic\Exceptions\ProviderAuthenticationException;
use LaraDantic\Exceptions\ProviderRateLimitException;
use LaraDantic\Exceptions\ProviderResponseException;
use LaraDantic\Providers\ProviderManager;

function chatCompletionResponse(array $overrides = []): array
{
    return array_replace_recursive([
        'model' => 'openrouter/test-model',
        'choices' => [
            [
                'index' => 0,
                'message' => ['role' => 'assistant', 'content' => 'Hello!'],
                'finish_reason' => 'stop',
            ],
        ],
        'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5, 'total_tokens' => 15],
    ], $overrides);
}

beforeEach(function () {
    config([
        'laradantic.providers.openrouter.api_key' => 'test-key',
        'laradantic.providers.openrouter.model' => 'openrouter/test-model',
        'laradantic.retries' => 0,
    ]);
});

it('sends a chat request and returns a normalized AIResponse', function () {
    Http::fake(['*/chat/completions' => Http::response(chatCompletionResponse())]);

    $response = app(ProviderManager::class)->driver('openrouter')->chat([
        ['role' => 'user', 'content' => 'Hi'],
    ]);

    expect($response->text())->toBe('Hello!')
        ->and($response->model())->toBe('openrouter/test-model')
        ->and($response->finishReason())->toBe('stop')
        ->and($response->hasToolCalls())->toBeFalse()
        ->and($response->usage()->inputTokens())->toBe(10)
        ->and($response->usage()->outputTokens())->toBe(5)
        ->and($response->usage()->totalTokens())->toBe(15);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://openrouter.ai/api/v1/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer test-key')
            && $request['model'] === 'openrouter/test-model';
    });
});

it('throws ProviderAuthenticationException without making a request when the API key is missing', function () {
    config(['laradantic.providers.openrouter.api_key' => '']);
    Http::fake();

    app(ProviderManager::class)->driver('openrouter')->chat([['role' => 'user', 'content' => 'Hi']]);
})->throws(ProviderAuthenticationException::class);

it('throws ProviderAuthenticationException on a 401 response', function () {
    Http::fake(['*/chat/completions' => Http::response(['error' => 'invalid key'], 401)]);

    app(ProviderManager::class)->driver('openrouter')->chat([['role' => 'user', 'content' => 'Hi']]);
})->throws(ProviderAuthenticationException::class);

it('throws ProviderRateLimitException on a 429 response after retries are exhausted', function () {
    Http::fake(['*/chat/completions' => Http::response(['error' => 'rate limited'], 429)]);

    app(ProviderManager::class)->driver('openrouter')->chat([['role' => 'user', 'content' => 'Hi']]);
})->throws(ProviderRateLimitException::class);

it('throws ProviderResponseException on a persistent 500 response', function () {
    Http::fake(['*/chat/completions' => Http::response(['error' => 'boom'], 500)]);

    app(ProviderManager::class)->driver('openrouter')->chat([['role' => 'user', 'content' => 'Hi']]);
})->throws(ProviderResponseException::class);

it('retries a transient failure and succeeds on a later attempt', function () {
    config(['laradantic.retries' => 2]);

    Http::fake([
        '*/chat/completions' => Http::sequence()
            ->push(['error' => 'boom'], 503)
            ->push(chatCompletionResponse()),
    ]);

    $response = app(ProviderManager::class)->driver('openrouter')->chat([['role' => 'user', 'content' => 'Hi']]);

    expect($response->text())->toBe('Hello!');
    Http::assertSentCount(2);
});

it('does not retry a non-retryable client error', function () {
    config(['laradantic.retries' => 3]);

    Http::fake(['*/chat/completions' => Http::response(['error' => 'bad request'], 400)]);

    app(ProviderManager::class)->driver('openrouter')->chat([['role' => 'user', 'content' => 'Hi']]);

    Http::assertSentCount(1);
})->throws(ProviderResponseException::class);

it('forwards tool definitions and exposes raw tool calls on the response', function () {
    Http::fake(['*/chat/completions' => Http::response(chatCompletionResponse([
        'choices' => [[
            'message' => [
                'role' => 'assistant',
                'content' => null,
                'tool_calls' => [[
                    'id' => 'call_1',
                    'type' => 'function',
                    'function' => ['name' => 'search_flight', 'arguments' => '{"origin":"VIE"}'],
                ]],
            ],
        ]],
    ]))]);

    $response = app(ProviderManager::class)->driver('openrouter')->tools(
        [['role' => 'user', 'content' => 'Find me a flight']],
        [['type' => 'function', 'function' => ['name' => 'search_flight']]],
    );

    expect($response->hasToolCalls())->toBeTrue()
        ->and($response->toolCalls()[0]['function']['name'])->toBe('search_flight');

    Http::assertSent(fn ($request) => isset($request['tools']));
});
