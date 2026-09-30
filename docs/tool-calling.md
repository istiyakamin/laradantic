# Tool calling

Ask a model to choose from a set of tools, then explicitly execute whichever one it picked:

```php
$toolCalls = AI::tools([CreateFlightBooking::class, SearchFlight::class])
    ->message('I want to book a flight from Vienna to Dhaka.')
    ->run(); // Illuminate\Support\Collection<int, ToolCall> - empty if the model replied with plain text

$toolCall = $toolCalls->first();

if ($toolCall->requiresConfirmation()) {
    // Ask the user/application to confirm before proceeding.
}

$result = $toolCall->execute(); // explicit - never automatic
```

See [tools.md](tools.md) for defining `CreateFlightBooking`/`SearchFlight` themselves.

## What happens between the raw response and a `ToolCall`

The model's response contains raw, untrusted JSON: `{"name": "create_flight_booking", "arguments": {...}}` (as an array of these, since a model can request multiple tool calls at once). `ToolCallRequest::run()` hands each one to `LaraDantic\Tools\ToolExecutor`, which:

1. Looks up the tool by name in the registry - throws `ToolException` if unknown.
2. Decodes `arguments` (accepts either a JSON string or an already-decoded array, since providers vary).
3. Validates and hydrates them via the tool's `schema()`, using `Schema::validated()` - the exact same pipeline as [validation.md](validation.md). Invalid/missing arguments throw `ToolValidationException` (with structured `errors()`) *before* a `ToolCall` is ever constructed.
4. Wraps the tool, the hydrated arguments, and the call's `id` into a `ToolCall`.

By the time you hold a `ToolCall`, its `->arguments()` are already a validated, typed `Schema` instance - never raw, unchecked model output.

## `ToolCall`

```php
$toolCall->id();                  // the provider's tool_call id (needed for the eventual tool-result message)
$toolCall->name();
$toolCall->arguments();           // Schema instance
$toolCall->schema();              // class-string<Schema>
$toolCall->requiresConfirmation();
$toolCall->execute(...$context);  // ToolResult - see below
```

`execute()` never throws: if the tool's own `execute()` method throws, `ToolCall::execute()` catches it and returns `ToolResult::error($exception)` instead. Extra arguments are forwarded positionally, for tools declaring `execute(FlightBooking $booking, User $user)`:

```php
$toolCall->execute($request->user());
```

## `ToolResult`

```php
ToolResult::success($data);        // $data can be a Schema, an enum, an array, or any plain value
ToolResult::failure('message');
ToolResult::error($exception);

$result->successful();
$result->failed();
$result->data();
$result->errorMessage();
$result->toArray(); // ['success' => bool, 'data' => ..., 'error' => ?string] - Schema/enum values normalized recursively
```

`toArray()` is what you feed back into the conversation as a `role: tool` message (see [agents.md](agents.md), which does this for you automatically in the loop).

## `AI::tools()` vs. building a `ToolRegistry` yourself

```php
AI::tools([CreateFlightBooking::class, SearchFlight::class])->message($message)->run();
```

is exactly equivalent to building a `ToolRegistry` and calling `ToolExecutor` directly - it's sugar for the common case, not a different code path. Reach for the lower-level classes directly if you need to reuse the same registry across multiple requests, or inspect `AIResponse` (raw text, `finishReason()`, `usage()`) alongside the parsed tool calls.
