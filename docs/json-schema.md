# JSON Schema

```php
FlightBooking::jsonSchema();      // array
FlightBooking::jsonSchemaJson();  // pretty-printed JSON string
```

This is the same document sent to an LLM provider for structured output or tool-calling (see [structured-output.md](structured-output.md) and [tool-calling.md](tool-calling.md)) - you don't maintain it separately.

## What gets generated

- `type: "object"`, `properties`, `required` (only for properties without a default/nullable type)
- Primitive properties → `string`/`integer`/`number`/`boolean`
- A nullable property gets `nullable: true` alongside its `type`
- Enums → `type` matching the backing type (`string`/`integer`) plus `enum: [...]`; pure enums → `type: "string"`, `enum: [...case names]`
- Nested `Schema` properties, and typed arrays of them → a full nested object schema, generated recursively (and cached per class)
- Typed arrays → `type: "array"` with an `items` schema for the declared item type
- A declared PHP default surfaces as the `default` keyword
- `mixed` properties produce an empty schema (`{}` - anything goes)

## Attributes

```php
use LaraDantic\Schema\Attributes\{Description, Example, Min, Max, MinLength, MaxLength, Pattern, Format, DefaultValue};

class FlightBooking extends Schema
{
    #[Description('Departure airport IATA code')]
    #[Example('VIE')]
    public string $origin;

    #[Min(1)]
    #[Max(20)]
    public int $passengers = 1;
}
```

| Attribute | JSON Schema keyword | Notes |
|---|---|---|
| `#[Description('...')]` | `description` | Also allowed on the class itself, for the top-level object schema |
| `#[Example('...')]` | `examples` (array) | Repeatable - stack multiple `#[Example]` attributes to list several |
| `#[Min($n)]` / `#[Max($n)]` | `minimum` / `maximum` | |
| `#[MinLength($n)]` / `#[MaxLength($n)]` | `minLength` / `maxLength` | |
| `#[Pattern('regex')]` | `pattern` | ECMA-262 regex body, no delimiters |
| `#[Format('email')]` | `format` | Free-form string - not validated against a fixed list |
| `#[DefaultValue($v)]` | `default` | Overrides/supplies a schema-level default independently of the property's native PHP default |

## What it deliberately doesn't do

- **No `$defs`/`$ref`.** Nested schemas are inlined in full at every occurrence, not deduplicated into shared definitions. This keeps the generator simple and keeps every generated schema independently valid and self-contained (important for providers that don't resolve `$ref`), at the cost of repeating a nested schema's structure if it's used in more than one place.
- **`nullable` is an OpenAPI-style flag, not `type: ["string", "null"]`.** Some strict JSON Schema consumers expect the latter form. If you're targeting one of those, post-process the generated array before sending it.
- **A property type LaraDantic can't represent** (a generic object that's neither a `Schema` nor an enum) throws `LaraDantic\Exceptions\UnsupportedFeatureException` rather than silently omitting it or guessing.
