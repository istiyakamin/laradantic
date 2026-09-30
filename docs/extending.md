# Extending

## Custom provider

Implement `LaraDantic\Providers\AIProvider` (see [providers.md](providers.md)) and register it:

```php
AI::extend('my-provider', new MyProvider());
AI::provider('my-provider')->chat($messages);
```

## Custom tool

Extend `LaraDantic\Tools\Tool` (see [tools.md](tools.md)):

```php
class MyTool extends Tool
{
    public function name(): string { return 'my_tool'; }
    public function description(): string { return '...'; }
    public function schema(): string { return MySchemaArgs::class; }

    public function execute(MySchemaArgs $args): ToolResult { /* ... */ }
}
```

Nothing needs registering globally - pass it directly to `AI::tools([MyTool::class])` or `new ToolRegistry()->register(MyTool::class)` wherever you build the tool set for a given request.

## Custom schema property attribute

`#[Description]`, `#[Min]`, etc. (see [json-schema.md](json-schema.md) and [validation.md](validation.md)) are read via `ReflectionProperty::getAttributes(SomeAttribute::class)` inside `JsonSchemaGenerator`/`RuleInferrer`. There's no formal extension point for *new* attribute types to automatically feed into schema/rule generation - if you add your own `#[MyAttribute]`, you'd read it the same way from your own code (e.g. a custom `Tool::toDefinition()` override, or your own rule-building step) rather than LaraDantic picking it up automatically. Attributes not recognized by LaraDantic's own generators are simply ignored by them, not an error.

## Custom serializer

`Schema::toArray()` always goes through `SchemaSerializer`. There's no pluggable serializer interface in this version - if you need a different output shape (e.g. camelCase keys, a different date format), transform `toArray()`'s output yourself at the boundary (a resource class, an API transformer) rather than changing how the schema serializes internally.

## What's *not* meant to be extended

Per the package's own design goals, LaraDantic deliberately stays out of:

- Authentication/authorization (bring your own; see [security.md](security.md))
- A booking/CRM/business-logic layer of any kind
- Being an ORM replacement, a chatbot frontend, or a prompt-management platform

If you find yourself wanting to extend LaraDantic into one of those, it's a sign that logic belongs in your application layer, using LaraDantic as infrastructure underneath it - not the other way around.
