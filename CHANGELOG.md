# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/) (with the understanding that, before `1.0.0`, breaking changes may occur between minor versions - see [CONTRIBUTING.md](CONTRIBUTING.md)).

## [Unreleased]

## [0.1.0] - 2026-09-30

Initial development release. Implements Milestones 1-7 of the original design brief.

### Added

- **Schema engine**: `Schema` base class with `from()`/`toArray()`, reflection-driven hydration and serialization, supporting primitives, nullable types, native PHP enums (backed and pure), nested `Schema` classes, typed arrays via `@var Type[]` docblocks, and declared property defaults.
- **JSON Schema generation**: `Schema::jsonSchema()`/`jsonSchemaJson()`, with recursive nested object schemas and the `#[Description]`, `#[Example]`, `#[Min]`, `#[Max]`, `#[MinLength]`, `#[MaxLength]`, `#[Pattern]`, `#[Format]`, and `#[DefaultValue]` property attributes.
- **Laravel validation integration**: `Schema::rules()` (auto-inferred, overridable), `Schema::validate()`/`validated()`, `SchemaValidationException` with structured field errors, nested/typed-array validation via dot and wildcard notation.
- **OpenRouter AI provider**: the provider-neutral `AIProvider` interface, `AIResponse`/`Usage`, `OpenRouterProvider` (Laravel HTTP client, configurable timeouts, retries limited to transient failures), and the `AI` facade/`AIManager` (`AI::structured()`, `AI::chat()`, `AI::provider()`/`AI::model()`, `AI::extend()` for custom providers).
- **Tool calling**: `Tool`, `ToolRegistry`, `ToolExecutor` (parses and validates raw tool calls into typed `ToolCall` objects), `ToolCall` (explicit execution only), `ToolResult`, `AI::tools()->message()->run()`.
- **Agent loop**: `AI::agent()` (LLM → tool call → tool execution → tool result → LLM), `AgentResult`/`AgentStatus`, a hard confirmation boundary that stops the loop before executing any tool requiring confirmation, and `Agent::resume()`.
- Full exception hierarchy under `LaraDanticException`: `SchemaException`, `SchemaValidationException`, `UnsupportedFeatureException`, `ProviderException` (+ `ProviderAuthenticationException`, `ProviderRateLimitException`, `ProviderResponseException`), `ToolException` (+ `ToolValidationException`).
- Laravel service provider, config file, `LaraDantic`/`AI` facades, CI (PHP 8.2–8.4 × Laravel 10–12), PHPStan level 8, Pint.

### Fixed

- `guzzlehttp/guzzle` is now a direct dependency, not an assumed transitive one - `illuminate/http` only suggests it, so installing this package in isolation (rather than into an app that happens to already require Guzzle) would otherwise fail at runtime the first time an AI provider call was made.

[Unreleased]: https://github.com/istiyakamin/laradantic/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/istiyakamin/laradantic/releases/tag/v0.1.0
