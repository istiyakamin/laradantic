# Tools

A tool pairs a `Schema` (its arguments) with an `execute()` method (what actually happens).

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
        // Your application logic - create the booking, call a service, etc.
        return ToolResult::success(['id' => 'BK-123']);
    }
}
```

## What each method is for

- **`name()`** - the identifier the LLM uses to call this tool. Keep it stable; changing it breaks any in-flight conversation that referenced it.
- **`description()`** - shown to the model; write it the way you'd explain the tool to a new team member, not a one-word label.
- **`schema()`** - returns the `Schema` class describing this tool's arguments. `toDefinition()` calls `$schemaClass::jsonSchema()` to build the function definition the provider sees - you never write that JSON Schema by hand.
- **`execute($arguments, ...$context)`** - your implementation. **Not declared on the abstract `Tool` class** (see below for why) - just define it with whatever concrete schema type and extra parameters make sense for this tool.
- **`requiresConfirmation()`** - defaults to `true`. Override to `false` only for tools with no side effects (a search, a lookup) - see [Security](#security) below.

## Why `execute()` isn't abstract

PHP's parameter variance rules only allow a subclass to *widen* an overridden method's parameter type, never narrow it. If `Tool` declared `abstract public function execute(Schema $arguments): ToolResult`, no subclass could narrow `$arguments` to a concrete type like `FlightBooking` - exactly the ergonomic signature you want. So `Tool` simply doesn't declare it; `ToolCall::execute()` (see [tool-calling.md](tool-calling.md)) invokes it dynamically via `ReflectionMethod`, and throws `ToolException` if a subclass forgot to define a public `execute()` method at all.

## Registering tools

```php
use LaraDantic\Tools\ToolRegistry;

$registry = new ToolRegistry();
$registry->register(CreateFlightBooking::class); // by class-string
$registry->register(new SearchFlight());          // or by instance

$registry->definitions(); // array of OpenAI-compatible function definitions, one per registered tool
```

`AI::tools([CreateFlightBooking::class, SearchFlight::class])` (see [tool-calling.md](tool-calling.md)) builds this registry for you from a plain array.

## Security

Extra positional arguments to `execute()` (an authenticated user, a tenant ID, etc.) are the mechanism for passing application context a tool needs but that shouldn't come from the LLM:

```php
public function execute(FlightBooking $booking, User $user): ToolResult
{
    // $user came from your application, not from the model
}
```

LaraDantic never assumes "the AI said it, therefore it is authorized" - authorization is always the host application's responsibility. See [security.md](security.md) for the full policy.
