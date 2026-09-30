# LaraDantic

Pydantic-inspired typed schemas for Laravel — define a data structure once in PHP and reuse it for validation, JSON Schema, structured AI output, and tool calling.

> **Status:** v0.1 in active development. This release implements the core schema engine (Milestone 2). Validation, JSON Schema generation, and AI/tool calling are not implemented yet — see the roadmap below.

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

### Supported types

- Primitives: `string`, `int`, `float`, `bool`
- Nullable variants of the above (`?string`, `?int`, ...)
- Native PHP enums (backed and pure)
- Nested `Schema` classes
- Typed arrays via `@var Type[]` docblocks (primitives, enums, or nested schemas)
- Declared property defaults (`public int $passengers = 1;`)

## Roadmap

This package is being built milestone by milestone. Implemented so far:

- [x] Milestone 1 — Package foundation (service provider, config, facade, exceptions, tests, static analysis, CI)
- [x] Milestone 2 — Core schema engine (reflection, types, serialization/deserialization)
- [ ] Milestone 3 — JSON Schema generation
- [ ] Milestone 4 — Laravel validation integration
- [ ] Milestone 5 — OpenRouter provider
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
