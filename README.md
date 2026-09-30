# LaraDantic

Pydantic-inspired typed schemas for Laravel — define a data structure once in PHP and reuse it for validation, JSON Schema, structured AI output, and tool calling.

> **Status:** v0.1 in active development. This release implements the full initial feature set — schema engine, JSON Schema generation, Laravel validation, structured AI output, tool calling, and the agent loop (Milestones 1-7). Remaining work is documentation and release hardening — see the roadmap below.

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

### Tool calling

Define a tool as a schema plus an `execute()` method:

```php
use LaraDantic\Tools\Tool;
use LaraDantic\Tools\ToolResult;

class CreateFlightBooking extends Tool
{
    public function name(): string
    {
        return 'create_flight_booking';
    }

    public function description(): string
    {
        return 'Create a flight booking request.';
    }

    public function schema(): string
    {
        return FlightBooking::class;
    }

    public function execute(FlightBooking $booking): ToolResult
    {
        // Application implementation - create the booking, return a result.
        return ToolResult::success(['id' => 'BK-123']);
    }
}
```

The tool's JSON Schema function definition is generated automatically from `FlightBooking::jsonSchema()`. Ask the model to call it:

```php
$toolCalls = AI::tools([CreateFlightBooking::class, SearchFlight::class])
    ->message('I want to book a flight from Vienna to Dhaka.')
    ->run(); // Collection<ToolCall> - empty if the model replied with plain text instead

$toolCall = $toolCalls->first();

if ($toolCall->requiresConfirmation()) {
    // Ask the user/application to confirm before proceeding.
}

$result = $toolCall->execute(); // explicit - never automatic
```

A `ToolCall`'s arguments are already validated and hydrated into the tool's declared schema (via `Schema::validated()`) by the time you see it - a malformed or invalid tool call throws `ToolException`/`ToolValidationException` (with structured `errors()`) before a `ToolCall` is ever created. `Tool::requiresConfirmation()` defaults to `true` (safe by default); override it to `false` only for genuinely read-only tools. Extra arguments passed to `->execute($user)` are forwarded positionally after the arguments, for tools declaring `execute(FlightBooking $booking, User $user)` - the host application remains responsible for authorization. `ToolCall::execute()` never throws: exceptions from inside a tool are caught and returned as a failed `ToolResult`.

### Agents

`AI::agent()` runs the LLM → tool call → tool execution → tool result → LLM loop for you, up to a configurable number of iterations:

```php
$result = AI::agent()
    ->tools([SearchFlight::class, CreateFlightBooking::class])
    ->maxIterations(5)
    ->system('You are a travel booking assistant.')
    ->run('I want to book a flight from Vienna to Dhaka for 2 people.');
```

Tools that don't require confirmation (`requiresConfirmation() === false`, e.g. a read-only `SearchFlight`) are executed automatically and their results fed straight back to the model, which keeps going until it produces a final reply. The moment the model calls a tool that *does* require confirmation, the loop stops immediately - **without executing it** - and returns control to you:

```php
match ($result->status()) {
    AgentStatus::Completed => $result->text(),
    AgentStatus::PendingConfirmation => /* ask the user, then see below */,
    AgentStatus::MaxIterationsReached => /* give up gracefully */,
};
```

To continue after the host has confirmed and explicitly executed the pending tool call(s):

```php
$toolCall = $result->pendingToolCalls()[0];
$toolResult = $toolCall->execute(); // explicit, after confirmation

$resumed = AI::agent()->tools([SearchFlight::class, CreateFlightBooking::class])
    ->resume($result->messages(), [['toolCall' => $toolCall, 'result' => $toolResult]]);
```

This confirmation boundary is a hard rule, not a configurable one: the agent loop never assumes "the AI said it, therefore it is authorized." `$result->executions()` lists every tool the loop *did* auto-execute (with its `ToolResult`), for auditing. `$result->messages()` is the running OpenAI-style conversation array - store it however your application prefers; LaraDantic doesn't force any persistence on you.

## Roadmap

This package is being built milestone by milestone. Implemented so far:

- [x] Milestone 1 — Package foundation (service provider, config, facade, exceptions, tests, static analysis, CI)
- [x] Milestone 2 — Core schema engine (reflection, types, serialization/deserialization)
- [x] Milestone 3 — JSON Schema generation (attributes, constraints, formats, nested definitions)
- [x] Milestone 4 — Laravel validation integration (rule inference, `SchemaValidationException`, nested validation)
- [x] Milestone 5 — OpenRouter provider (structured output, chat, tool-call forwarding, retries)
- [x] Milestone 6 — Tool calling (`Tool`, `ToolRegistry`, `ToolCall`, `ToolResult`, explicit execution, confirmation)
- [x] Milestone 7 — Agent loop (`AI::agent()`, max iterations, auto-execution up to the confirmation boundary, `resume()`)

## Testing

```bash
composer install
composer test
composer stan
composer pint
```

## License

MIT
