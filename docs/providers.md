# Providers

LaraDantic is provider-agnostic by design (section 3.4/19 of the original design brief): OpenRouter is the only built-in provider, but nothing in the schema, JSON Schema, validation, or tool-calling layers depends on it.

## The contract

```php
interface LaraDantic\Providers\AIProvider
{
    public function chat(array $messages, array $options = []): AIResponse;
    public function structured(array $messages, array $schema, array $options = []): AIResponse;
    public function tools(array $messages, array $tools, array $options = []): AIResponse;
}
```

`$messages` is the standard OpenAI-compatible array (`[['role' => 'user', 'content' => '...'], ...]`). `structured()` receives a JSON Schema array (from `Schema::jsonSchema()`); `tools()` receives an array of OpenAI-compatible function definitions (from `ToolRegistry::definitions()`). Both return the same provider-neutral `AIResponse` that `chat()` does.

## `AIResponse`

```php
$response->text();          // ?string
$response->hasToolCalls();  // bool
$response->toolCalls();     // raw array<{id, type, function: {name, arguments}}> - see tool-calling.md
$response->model();         // ?string
$response->usage();         // ?Usage (->inputTokens()/->outputTokens()/->totalTokens())
$response->finishReason();  // ?string
$response->raw();           // the full decoded provider response, for anything not otherwise exposed
```

## Selecting a provider/model

```php
AI::chat($messages);                                    // uses config('laradantic.default_provider')
AI::provider('openrouter')->chat($messages);
AI::provider('openrouter')->model('some-model')->chat($messages);
```

`->provider()`/`->model()` return a new `AIManager` instance rather than mutating a shared one - each call is independent and side-effect-free.

## Writing a custom provider

```php
class MyProvider implements \LaraDantic\Providers\AIProvider
{
    public function chat(array $messages, array $options = []): AIResponse { /* ... */ }
    public function structured(array $messages, array $schema, array $options = []): AIResponse { /* ... */ }
    public function tools(array $messages, array $tools, array $options = []): AIResponse { /* ... */ }
}
```

Register it at runtime:

```php
AI::extend('my-provider', new MyProvider());

AI::provider('my-provider')->structured(FlightBooking::class)->prompt($prompt)->run();
```

`AI::extend()` registers the provider on the underlying `LaraDantic\Providers\ProviderManager` (container singleton), so it's available to every subsequent `AI::provider('my-provider')` call for the rest of the request/process - typically called once, e.g. from a service provider's `boot()`.

See [openrouter.md](openrouter.md) for the built-in provider, and [extending.md](extending.md) for more on custom providers/tools.
