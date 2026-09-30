# Schemas

A schema is a plain PHP class extending `LaraDantic\Schema\Schema` with typed public properties. It's the single source of truth: the same class definition drives hydration, serialization, JSON Schema generation, and validation.

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

## Hydrating: `Schema::from()`

```php
$booking = FlightBooking::from([
    'origin' => 'VIE',
    'destination' => 'DAC',
    'departureDate' => '2026-10-15',
    'returnDate' => null,
    'passengers' => 2,
    'cabin' => 'business',
]);
```

`from()` reflects the class once (cached per class - see [Performance](#performance) below) and, for every declared public property:

1. If the key is present in `$data`, hydrates and type-casts the value (see [types.md](types.md) for exactly what's accepted for each type).
2. Otherwise, if the property has a PHP default (`public int $passengers = 1;`), uses that default.
3. Otherwise, if the property's type is nullable (`?string`), sets `null`.
4. Otherwise, throws `LaraDantic\Exceptions\SchemaException` - the property is required and nothing was given.

`from()` never runs against Laravel's validation engine or checks attribute constraints (`#[Min]`, `#[Pattern]`, etc.) - it only enforces the *structural* contract (required/nullable/type). For full validation, see [validation.md](validation.md) and use `Schema::validated()` instead.

A value that's the wrong shape for its declared type (e.g. `'passengers' => 'not-a-number'`) also throws `SchemaException`, not a validation error - this is a structural failure, not a business-rule failure.

## Serializing: `toArray()`

```php
$booking->toArray();
// ['origin' => 'VIE', 'destination' => 'DAC', ..., 'cabin' => 'business']
```

Backed enums serialize to their scalar `->value`; pure enums to their `->name`; nested `Schema` properties recurse via their own `toArray()`; arrays are mapped recursively. Round-tripping `from($data)->toArray()` reproduces `$data` exactly for well-formed input.

## Required vs. optional

| Declaration | Behavior |
|---|---|
| `public string $origin;` | Required - `from()` throws if missing |
| `public ?string $returnDate;` | Optional - defaults to `null` if missing |
| `public int $passengers = 1;` | Optional - defaults to `1` if missing |

## Nested schemas

```php
class Passenger extends Schema
{
    public string $name;
    public string $passportNumber;
}

class FlightBooking extends Schema
{
    /** @var Passenger[] */
    public array $passengers;
}
```

`FlightBooking::from([..., 'passengers' => [['name' => 'Jane Doe', 'passportNumber' => 'X1234567']]])` recursively hydrates each array element into a `Passenger` instance. A single (non-array) nested `Schema` property works the same way without the docblock array syntax.

## Performance

Reflection metadata (which properties exist, their resolved types, attributes) is computed once per schema class and cached in memory for the lifetime of the request/process (`LaraDantic\Schema\Reflection\SchemaReflection::for()`). Repeated `from()`/`toArray()`/`jsonSchema()` calls on the same class don't re-reflect.
