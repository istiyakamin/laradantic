# OpenRouter provider

The built-in `LaraDantic\Providers\OpenRouter\OpenRouterProvider` talks to [OpenRouter](https://openrouter.ai)'s OpenAI-compatible `/chat/completions` endpoint via Laravel's HTTP client.

## Configuration

```env
OPENROUTER_API_KEY=
OPENROUTER_MODEL=
OPENROUTER_BASE_URL=https://openrouter.ai/api/v1
```

See [configuration.md](configuration.md) for the full config file. The API key is read only from config/env - it is never logged, and never appears in any exception message this package throws.

## Structured output

`AI::structured($schemaClass)->run()` sends `response_format: {type: "json_schema", json_schema: {name, strict: false, schema}}`, where `schema` is `$schemaClass::jsonSchema()` and `name` defaults to the schema class's short name (override via `->options(['schema_name' => '...'])`). `strict` is `false` by default because LaraDantic's generated schema (optional/nullable properties represented via `nullable: true` rather than every key being required, per OpenAI's strict-mode rules) isn't guaranteed to satisfy every model's strict-mode requirements - many OpenRouter-routed models only support the looser form anyway. Pass `->options(['response_format' => [...]])` to fully replace this default if you're targeting a model you know supports strict mode.

## Retries

Configurable via `config('laradantic.retries')` (default `0`, meaning one attempt, no retries). Only genuinely transient failures are retried:

- HTTP 429, 500, 502, 503, 504
- Connection-level failures (DNS, timeout, refused connection)

Anything else (400, 401, 403, 404, a malformed response) fails immediately on the first attempt - retrying a request that's wrong in a way a retry can't fix just wastes time and quota.

## Errors

| Situation | Exception |
|---|---|
| No API key configured | `ProviderAuthenticationException` |
| HTTP 401/403 | `ProviderAuthenticationException` |
| HTTP 429, retries exhausted | `ProviderRateLimitException` |
| Any other failed response | `ProviderResponseException` |
| No model configured (and none passed via `->model()`) | `ProviderException` |
| Structured output response wasn't valid JSON | `ProviderResponseException` |

All of these extend `LaraDantic\Exceptions\ProviderException` → `LaraDanticException`, so catching the base class covers every provider-level failure.

## Testing against it

Never hits the real API in this package's own test suite, and shouldn't in yours either:

```php
Http::fake(['*/chat/completions' => Http::response([
    'model' => 'openrouter/some-model',
    'choices' => [['message' => ['role' => 'assistant', 'content' => 'Hello!'], 'finish_reason' => 'stop']],
])]);
```

See [testing.md](testing.md) for fuller examples (structured output, tool calls, retries).
