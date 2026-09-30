# Validation

Rules are inferred automatically from the same schema you already defined - you never have to hand-maintain a parallel Laravel `rules()` array for simple cases.

## Inferred rules

For each property, `Schema::rules()` produces:

| Schema shape | Inferred rule(s) |
|---|---|
| Required property (no default, not nullable) | `required` |
| `?string $x;` | `nullable` |
| Property with a PHP default | `sometimes` |
| `string` | `string` |
| `int` | `integer` |
| `float` | `numeric` |
| `bool` | `boolean` |
| Backed/pure enum | `Illuminate\Validation\Rule::in([...case values/names])` |
| Nested `Schema` property | `array`, plus that schema's own rules prefixed `property.` |
| `/** @var X[] */ array $items;` | `array`, plus `items.*` rules for the item type (including a full nested rule set, prefixed `items.*.`, when `X` is itself a `Schema`) |

Plus, from attributes on the property:

| Attribute | Rule |
|---|---|
| `#[Min(1)]` | `min:1` |
| `#[Max(20)]` | `max:20` |
| `#[MinLength(2)]` | `min:2` |
| `#[MaxLength(255)]` | `max:255` |
| `#[Pattern('^[A-Z]{3}$')]` | `regex:/^[A-Z]{3}$/` |
| `#[Format('email')]` | `email` (also: `url`/`uri`, `uuid`, `date`, `date-time`/`datetime` → `date`, `ipv4`, `ipv6`; unrecognized formats are skipped) |

## Checking without throwing

```php
$result = FlightBooking::validate($data);

if ($result->fails()) {
    $result->errors(); // ['passengers' => ['The passengers field must not be greater than 20.']]
}
```

`ValidationResult` mirrors Laravel's own validator shape: `passes()`, `fails()`, `errors()` (a plain `field => [messages]` array, exactly Laravel's `MessageBag::toArray()`).

## Validate-then-hydrate

```php
$booking = FlightBooking::validated($data); // throws SchemaValidationException on failure
```

`SchemaValidationException::errors()` exposes the same structured array. This is the method to reach for when invalid data should simply stop the request (e.g. inside a controller or a tool call handler) rather than being handled explicitly.

**`Schema::from()` is unaffected by any of this.** It performs its own lighter, purely structural check (required/nullable/type-castable) and throws `SchemaException`, not `SchemaValidationException` - it doesn't run Laravel's validation engine or look at attribute constraints at all. Use `from()` when you already trust the shape of the data (e.g. re-hydrating your own `toArray()` output) and `validate()`/`validated()` when the data is external input that needs real validation.

## Overriding the rules

Define `rules()` yourself to replace inference entirely:

```php
class CustomRulesBooking extends Schema
{
    public string $reference;

    public static function rules(): array
    {
        return [
            'reference' => ['required', 'string', 'starts_with:BK-'],
        ];
    }
}
```

There's no merge step - once you define `rules()`, it's authoritative for that class.
