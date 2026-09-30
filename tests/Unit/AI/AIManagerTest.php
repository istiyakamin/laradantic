<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use LaraDantic\AI\AIManager;
use LaraDantic\AI\AIResponse;
use LaraDantic\Exceptions\ProviderException;
use LaraDantic\Exceptions\ProviderResponseException;
use LaraDantic\Providers\AIProvider;
use LaraDantic\Providers\ProviderManager;
use LaraDantic\Tests\Fixtures\DemoQuery;

beforeEach(function () {
    config([
        'laradantic.providers.openrouter.api_key' => 'test-key',
        'laradantic.providers.openrouter.model' => 'openrouter/test-model',
        'laradantic.retries' => 0,
    ]);
});

function structuredCompletionResponse(string $json): array
{
    return [
        'model' => 'openrouter/test-model',
        'choices' => [[
            'index' => 0,
            'message' => ['role' => 'assistant', 'content' => $json],
            'finish_reason' => 'stop',
        ]],
    ];
}

it('runs a structured output request end-to-end into a validated schema instance', function () {
    Http::fake([
        '*/chat/completions' => Http::response(
            structuredCompletionResponse(json_encode(['answer' => 'Paris', 'confidence' => 87]))
        ),
    ]);

    $result = app('laradantic.ai')
        ->structured(DemoQuery::class)
        ->system('You are helpful.')
        ->prompt('What is the capital of France?')
        ->run();

    expect($result)->toBeInstanceOf(DemoQuery::class)
        ->and($result->answer)->toBe('Paris')
        ->and($result->confidence)->toBe(87);

    Http::assertSent(function ($request) {
        return $request['response_format']['type'] === 'json_schema'
            && $request['response_format']['json_schema']['schema']['type'] === 'object'
            && $request['messages'][0] === ['role' => 'system', 'content' => 'You are helpful.'];
    });
});

it('throws when run() is called without a prompt', function () {
    app('laradantic.ai')->structured(DemoQuery::class)->run();
})->throws(ProviderException::class);

it('throws ProviderResponseException when the provider returns non-JSON content', function () {
    Http::fake(['*/chat/completions' => Http::response(structuredCompletionResponse('not json'))]);

    app('laradantic.ai')->structured(DemoQuery::class)->prompt('Anything')->run();
})->throws(ProviderResponseException::class);

it('applies ->model() as the request model', function () {
    Http::fake([
        '*/chat/completions' => Http::response(
            structuredCompletionResponse(json_encode(['answer' => 'x']))
        ),
    ]);

    app('laradantic.ai')->model('openrouter/other-model')
        ->structured(DemoQuery::class)
        ->prompt('Anything')
        ->run();

    Http::assertSent(fn ($request) => $request['model'] === 'openrouter/other-model');
});

it('lets a custom provider be registered and resolved by name', function () {
    $fake = new class implements AIProvider
    {
        public function chat(array $messages, array $options = []): AIResponse
        {
            return new AIResponse('fake response', [], 'fake-model', 'stop', null, []);
        }

        public function structured(array $messages, array $schema, array $options = []): AIResponse
        {
            return $this->chat($messages, $options);
        }

        public function tools(array $messages, array $tools, array $options = []): AIResponse
        {
            return $this->chat($messages, $options);
        }
    };

    /** @var AIManager $manager */
    $manager = app('laradantic.ai');
    $manager->extend('fake', $fake);

    $response = $manager->provider('fake')->chat([['role' => 'user', 'content' => 'Hi']]);

    expect($response->text())->toBe('fake response');
});

it('throws for an unregistered provider name', function () {
    app(ProviderManager::class)->driver('does-not-exist');
})->throws(ProviderException::class);
