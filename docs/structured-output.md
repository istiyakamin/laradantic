# Structured AI output

Turn free-text user input into a validated, typed object:

```php
$booking = AI::structured(FlightBooking::class)
    ->system('You are a travel booking assistant.')
    ->prompt('Fly VIE to DAC on 2026-10-15 for 2 people, business class.')
    ->run();

$booking instanceof FlightBooking; // true
```

## The pipeline

```
FlightBooking
     │
     ▼
FlightBooking::jsonSchema()
     │
     ▼
provider request (response_format: json_schema)
     │
     ▼
LLM
     │
     ▼
AIResponse::text()  (a JSON string)
     │
     ▼
json_decode()
     │
     ▼
FlightBooking::validated($decoded)   (validate, then hydrate)
     │
     ▼
FlightBooking instance
```

This is exactly `Schema::validated()` from [validation.md](validation.md) - if the model's output doesn't satisfy the schema's rules (inferred or overridden), you get `SchemaValidationException` with structured errors, not a silently-wrong object.

## Builder methods

- `->system(string $prompt)` - optional; prepended as a `system` message
- `->prompt(string $prompt)` - required; the `run()` call throws `ProviderException` if this was never set
- `->options(array $options)` - merged into the outgoing request payload (model parameters, `response_format` override, etc.)

`AI::provider('...')`/`AI::model('...')` compose with `structured()` the same way they do with every other entry point - see [providers.md](providers.md).

## Failure modes

| Situation | What happens |
|---|---|
| Provider/network error | `ProviderException` and subclasses - see [openrouter.md](openrouter.md) |
| Model's response content isn't valid JSON | `ProviderResponseException` |
| Model's response *is* valid JSON but fails schema validation | `SchemaValidationException` (structured `errors()`) |
| No `->prompt()` was set | `ProviderException` |

Nothing here executes side effects - `AI::structured()` only ever produces a value. For AI-driven actions (create a booking, send an email, etc.), see [tool-calling.md](tool-calling.md) and [agents.md](agents.md), where execution is always explicit.
