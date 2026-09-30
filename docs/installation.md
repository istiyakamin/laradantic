# Installation

## Requirements

- PHP 8.2+
- Laravel 10, 11, or 12 (`illuminate/support` and `illuminate/validation`)
- Guzzle 7.2+ (required directly by this package, since `illuminate/http` only *suggests* it rather than requiring it)

## Install

```bash
composer require istiyakamin/laradantic
```

The service provider (`LaraDantic\Laravel\LaraDanticServiceProvider`) and the `LaraDantic`/`AI` facade aliases are registered automatically via Laravel's package discovery - no manual registration needed.

## Publish the config (optional)

```bash
php artisan vendor:publish --tag=laradantic-config
```

This publishes `config/laradantic.php`, where you can set the default AI provider, per-provider settings, and timeouts/retries. See [configuration.md](configuration.md).

## Environment variables

```env
OPENROUTER_API_KEY=
OPENROUTER_MODEL=
OPENROUTER_BASE_URL=https://openrouter.ai/api/v1
LARADANTIC_PROVIDER=openrouter
LARADANTIC_REQUEST_TIMEOUT=60
LARADANTIC_CONNECT_TIMEOUT=10
LARADANTIC_RETRIES=0
```

None of these are required unless you use the AI/structured-output/tool-calling/agent features - the schema, JSON Schema, and validation layers have no external dependencies and work with zero configuration.

## Verifying the install

```php
use LaraDantic\Schema\Schema;

class Ping extends Schema
{
    public string $message = 'pong';
}

Ping::from([])->message; // 'pong'
```

If that runs without error, the schema engine is wired up correctly. See [schemas.md](schemas.md) to start defining your own.
