# Security

This page documents the security posture of the package's design - for reporting an actual vulnerability, see [SECURITY.md](../SECURITY.md) at the repo root.

## The core rule

**LaraDantic never assumes "the AI said it, therefore it is authorized."** An LLM's output is untrusted input, same category as a raw HTTP request body - it gets validated the same way, and it never gets to trigger a side effect on its own.

Concretely, this shows up as:

- **Tool execution is always explicit.** Parsing a model's tool call into a `ToolCall` (`ToolExecutor::parse()`) never runs anything. Only an explicit `$toolCall->execute()` call does - see [tool-calling.md](tool-calling.md).
- **`Tool::requiresConfirmation()` defaults to `true`.** A tool author has to actively opt out (return `false`) for a tool to be eligible for automatic execution inside an agent loop; the safe default is "ask first."
- **The agent loop stops, it doesn't ask permission and continue.** The moment `AI::agent()`'s loop encounters a tool requiring confirmation, it returns control to the host application immediately, without executing it - see [agents.md](agents.md#the-confirmation-boundary). There's no configuration flag to make the loop auto-execute a confirmation-required tool; that boundary is load-bearing, not a default.
- **Tool arguments are validated before you ever see them,** via the same Laravel validation pipeline as everything else in this package (see [validation.md](validation.md)) - a malformed or out-of-range tool call throws before a `ToolCall` object exists, rather than reaching your `execute()` method.
- **Authorization is the host application's job, not this package's.** A tool's `execute()` method can (and should) accept extra parameters - an authenticated `User`, a tenant ID - passed explicitly by your application, never inferred from the model's output. LaraDantic has no concept of "current user" or permissions.

## API keys

- Read only from config (`config('laradantic.providers.openrouter.api_key')`), which in turn should read only from an environment variable - never commit a key to a config file's default value.
- Never included in any exception message this package throws. `ProviderAuthenticationException`/`ProviderResponseException` messages describe the HTTP status and, for response errors, a truncated response body - never the request headers or payload.
- Never logged by this package directly. (If you enable Laravel's own HTTP client logging/`Http::fake()`'s recording in your application, that's your application's logging configuration, not this package's.)

## What this package doesn't do

- It doesn't cache AI responses by default, and doesn't automatically persist prompts/conversations - see [agents.md](agents.md#no-forced-persistence).
- It doesn't implement authentication, authorization, rate limiting for your endpoints, or any application-level access control. Those remain entirely your application's responsibility, same as with any other Laravel package.
- It doesn't retry side-effecting operations automatically - the retry policy (see [openrouter.md](openrouter.md#retries)) applies only to the *provider request itself* (getting a response from the LLM), never to tool execution.

## Reporting a vulnerability

See [SECURITY.md](../SECURITY.md).
