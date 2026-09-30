# LaraDantic

Pydantic-inspired typed schemas for Laravel — define a data structure once in PHP and reuse it for validation, JSON Schema, structured AI output, and tool calling.

> **Status:** v0.1 in active development. This release implements the core schema engine, JSON Schema generation, Laravel validation, and structured AI output via OpenRouter (Milestones 1-5). Tool calling and agents are not implemented yet — see the roadmap below.

## Installation

```bash
composer require istiyakamin/laradantic
```

## Quick start

Define a schema once:

```php
use LaraDantic\Schema\Schema;

class FlightBooking extends Schema
{
    public string $origin;

    public string $destination;

    public string $departureDate;

    public ?string $returnDate;

    public int $passengers = 1;

    public Cabin $cabin;
}
```

Hydrate it from raw array data (e.g. a request payload or an LLM tool call):

```php
$booking = FlightBooking::from([
    'origin' => 'VIE',
    'destination' => 'DAC',
    'departureDate' => '2026-10-15',
    'returnDate' => null,
    'passengers' => 2,
    'cabin' => 'business',
]);

$booking->cabin; // Cabin::Business
```

Serialize it back into a plain array:

```php
$booking->toArray();
```

Missing required properties or values that can't be cast to the declared type throw a `LaraDantic\Exceptions\SchemaException`.

Generate its JSON Schema:

```php
use LaraDantic\Schema\Attributes\Description;
use LaraDantic\Schema\Attributes\Example;
use LaraDantic\Schema\Attributes\Max;
use LaraDantic\Schema\Attributes\Min;

class FlightBooking extends Schema
{
    #[Description('Departure airport IATA code')]
    #[Example('VIE')]
    public string $origin;

    #[Min(1)]
    #[Max(20)]
    public int $passengers = 1;

    // ...
}

FlightBooking::jsonSchema();
FlightBooking::jsonSchemaJson(); // pretty-printed JSON string
```

### Supported types

- Primitives: `string`, `int`, `float`, `bool`
- Nullable variants of the above (`?string`, `?int`, ...)
- Native PHP enums (backed and pure)
- Nested `Schema` classes
- Typed arrays via `@var Type[]` docblocks (primitives, enums, or nested schemas)
- Declared property defaults (`public int $passengers = 1;`)

### JSON Schema

`Schema::jsonSchema()` / `Schema::jsonSchemaJson()` produce a JSON Schema document with:

- `type`, `properties`, `required`, `nullable`, `enum`, `default`
- Nested object schemas for `Schema` properties and typed arrays of them
- `description` / `examples` / `minimum` / `maximum` / `minLength` / `maxLength` / `pattern` / `format`, via the `#[Description]`, `#[Example]`, `#[Min]`, `#[Max]`, `#[MinLength]`, `#[MaxLength]`, `#[Pattern]`, `#[Format]` and `#[DefaultValue]` attributes on properties (`#[Description]` is also allowed on the class itself)

### Validation

Rules are inferred from the same schema — required/nullable/optional presence, native type, enums (`Rule::in`), nested `Schema` properties and typed arrays of them via dot/wildcard notation (`passengers_list.*.name`), plus the `#[Min]`/`#[Max]`/`#[MinLength]`/`#[MaxLength]`/`#[Pattern]`/`#[Format]` attributes. Override `rules()` on your schema to replace them entirely.

```php
$result = FlightBooking::validate($data);

if ($result->fails()) {
    $result->errors(); // ['passengers' => ['The passengers field must not be greater than 20.']]
}

// Or validate-then-hydrate in one call, throwing on failure:
$booking = FlightBooking::validated($data); // throws SchemaValidationException
```

`SchemaValidationException::errors()` exposes the same structured `field => [messages]` array. Note that `Schema::from()` itself is unchanged from Milestone 2 — it still throws the lighter-weight `SchemaException` for missing/uncastable fields; `validate()`/`validated()` are the new, opt-in Laravel-rules layer.

### Structured AI output (OpenRouter)

```env
OPENROUTER_API_KEY=
OPENROUTER_MODEL=
```

```php
$booking = AI::structured(FlightBooking::class)
    ->system('You are a travel booking assistant.')
    ->prompt('Fly VIE to DAC on 2026-10-15 for 2 people, business class.')
    ->run(); // FlightBooking instance, validated and hydrated
```

The pipeline is exactly: schema -> JSON Schema -> provider request -> LLM -> JSON decode -> `Schema::validated()` -> typed object. `AI::provider('openrouter')->model('...')`, `->chat($messages)`, and registering a custom provider via `AI::extend('name', $provider)` (any `LaraDantic\Providers\AIProvider` implementation) are also supported. Requests use Laravel's HTTP client, so `Http::fake()` works in tests — no other provider is required yet, but the `AIProvider` interface is provider-neutral by design.

Transient failures (429/500/502/503/504, connection errors) are retried automatically up to `config('laradantic.retries')` times; everything else fails fast as `ProviderAuthenticationException` (401/403 or a missing API key), `ProviderRateLimitException` (429, retries exhausted), or `ProviderResponseException` (other errors, or non-JSON structured output).

## Roadmap

This package is being built milestone by milestone. Implemented so far:

- [x] Milestone 1 — Package foundation (service provider, config, facade, exceptions, tests, static analysis, CI)
- [x] Milestone 2 — Core schema engine (reflection, types, serialization/deserialization)
- [x] Milestone 3 — JSON Schema generation (attributes, constraints, formats, nested definitions)
- [x] Milestone 4 — Laravel validation integration (rule inference, `SchemaValidationException`, nested validation)
- [x] Milestone 5 — OpenRouter provider (structured output, chat, tool-call forwarding, retries)
- [ ] Milestone 6 — Tool calling
- [ ] Milestone 7 — Agent loop

## Testing

```bash
composer install
composer test
composer stan
composer pint
```

## License

MIT
