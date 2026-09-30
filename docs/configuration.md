# Configuration

`config/laradantic.php` (publish it first - see [installation.md](installation.md)):

```php
return [
    'default_provider' => env('LARADANTIC_PROVIDER', 'openrouter'),

    'providers' => [
        'openrouter' => [
            'api_key' => env('OPENROUTER_API_KEY'),
            'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
            'model' => env('OPENROUTER_MODEL'),
        ],
    ],

    'request_timeout' => env('LARADANTIC_REQUEST_TIMEOUT', 60),
    'connect_timeout' => env('LARADANTIC_CONNECT_TIMEOUT', 10),
    'retries' => env('LARADANTIC_RETRIES', 0),
];
```

## Keys

- **`default_provider`** - which entry under `providers` `AI::provider()`/`ProviderManager::driver()` resolves to when no provider name is given explicitly.
- **`providers.openrouter.api_key`** - never hard-code this; always source it from an environment variable. It is never logged by this package.
- **`providers.openrouter.base_url`** - override if you're proxying OpenRouter through your own gateway.
- **`providers.openrouter.model`** - the default model used when a request doesn't specify one via `->model()`.
- **`request_timeout` / `connect_timeout`** - seconds, passed straight to Laravel's HTTP client (`->timeout()` / `->connectTimeout()`).
- **`retries`** - the number of *additional* attempts after the first on a transient failure (429/500/502/503/504/connection errors only - see [openrouter.md](openrouter.md)). `0` (the default) means no retries.

## Per-request overrides

Every config value has a fluent equivalent that doesn't require touching the config file:

```php
AI::provider('openrouter')->model('some-other-model')->chat($messages);

AI::structured(FlightBooking::class)->options(['temperature' => 0.2])->prompt($prompt)->run();
```

`->options()` merges raw key/value pairs directly into the outgoing request payload, so any provider-specific parameter (`temperature`, `top_p`, etc.) can be passed through without LaraDantic needing to know about it.
