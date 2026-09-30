# Testing

## In this package itself

```bash
composer install
composer test   # Pest, via Orchestra Testbench (a real, in-memory Laravel app)
composer stan    # PHPStan level 8
composer pint    # Laravel Pint (code style)
```

No test in this suite makes a real network call to any AI provider - every provider-facing test uses Laravel's `Http::fake()`. If you're contributing a change that touches `OpenRouterProvider` or any other provider, keep it that way.

## In an application using LaraDantic

### Schemas and validation

Plain unit tests - no Laravel HTTP faking needed:

```php
it('hydrates a flight booking', function () {
    $booking = FlightBooking::from(['origin' => 'VIE', /* ... */]);
    expect($booking->origin)->toBe('VIE');
});

it('rejects an invalid cabin value', function () {
    expect(FlightBooking::validate(['cabin' => 'not-a-real-cabin', /* ... */])->fails())->toBeTrue();
});
```

### AI-facing code (structured output, tools, agents)

Fake the HTTP layer, exactly as this package's own tests do:

```php
use Illuminate\Support\Facades\Http;

it('books a flight from a natural-language prompt', function () {
    Http::fake(['*/chat/completions' => Http::response([
        'model' => 'openrouter/some-model',
        'choices' => [['message' => [
            'role' => 'assistant',
            'content' => json_encode(['origin' => 'VIE', 'destination' => 'DAC', /* ... */]),
        ], 'finish_reason' => 'stop']],
    ])]);

    $booking = AI::structured(FlightBooking::class)->prompt('...')->run();

    expect($booking->origin)->toBe('VIE');
});
```

For tool calls and agent loops, fake a `tool_calls` response the same way (see the `agentToolCallResponse()`/`toolCallCompletionResponse()` helpers in this package's own `tests/Unit/AI/*Test.php` files for the exact shape) and, for multi-turn agent loops, `Http::fake(['*/chat/completions' => Http::sequence()->push(...)->push(...)])` to script each turn.

### Custom tools

A `Tool` is a plain class - test it directly without going through the AI layer at all:

```php
it('creates a booking', function () {
    $result = (new CreateFlightBooking())->execute(
        FlightBooking::from([/* ... */])
    );

    expect($result->successful())->toBeTrue();
});
```

Or via `ToolCall` if you want to exercise the confirmation-flag/exception-catching behavior too - see [tool-calling.md](tool-calling.md).
