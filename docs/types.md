# Types

## Primitives

`string`, `int`, `float`, `bool`. Input is cast leniently on hydration - a numeric string like `"3"` is accepted for an `int` property, a JSON number is accepted for a `float` property, etc. - because real-world input (HTTP request bodies, LLM tool call arguments) rarely arrives with exact native PHP types. A value that genuinely can't be cast (`"passengers" => "not-a-number"`) throws `SchemaException`.

## Nullable

`?string`, `?int`, `?float`, `?bool`. A nullable property that's missing from the input defaults to `null`; an explicit `null` value is always accepted regardless of the underlying type.

## Native PHP enums

Both backed and pure enums work:

```php
enum Cabin: string
{
    case Economy = 'economy';
    case Business = 'business';
}

public Cabin $cabin;
```

Hydration accepts either the enum's backing value (`'business'`) or an already-constructed enum instance. An unrecognized value throws `SchemaException`. Pure (non-backed) enums are matched by case name instead.

## Nested schemas

Any property typed as another `Schema` subclass is recursively hydrated from a nested array, or accepted as-is if already an instance:

```php
class Passenger extends Schema { public string $name; }

class FlightBooking extends Schema
{
    public Passenger $leadPassenger;
}
```

## Typed arrays

PHP has no generics, so array item types are declared via a docblock, resolved against the property's declaring class namespace:

```php
/** @var Passenger[] */
public array $passengers;

/** @var string[] */
public array $tags;
```

Supported item types: primitives, enums, and nested `Schema` classes. An `array` property with no `@var Type[]` docblock is treated as untyped - its contents pass through as given, with no per-item hydration.

## Declared defaults

```php
public int $passengers = 1;
```

A native PHP default makes the property optional; the schema generator also surfaces it as JSON Schema's `default` keyword (see [json-schema.md](json-schema.md)).

## What's not (yet) supported

- **`DateTimeInterface`/`Carbon`** - not implemented. Use a `string` property with a `#[Format('date')]` or `#[Format('date-time')]` attribute (see [json-schema.md](json-schema.md)) and parse it yourself, for now.
- **Arbitrary generic objects** (a class that's neither an enum nor a `Schema` subclass) - accepted only if the input value is *already* an instance of that class; there's no hydration path for constructing one from an array, since LaraDantic has no way to know how. `jsonSchema()`/`rules()` generation for such a property throws `UnsupportedFeatureException`.
- **Union types** (`string|int $x`) - throws `SchemaException` at reflection time. Use `mixed` if you genuinely need to accept anything.
