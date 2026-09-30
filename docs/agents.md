# Agents

`AI::agent()` runs the full loop for you:

```
message → LLM → tool call → tool execution → tool result → LLM → ... → final reply
```

```php
$result = AI::agent()
    ->tools([SearchFlight::class, CreateFlightBooking::class])
    ->maxIterations(5)
    ->system('You are a travel booking assistant.')
    ->run('I want to book a flight from Vienna to Dhaka for 2 people.');
```

Read [tools.md](tools.md) and [tool-calling.md](tool-calling.md) first if you haven't - an agent is built entirely out of those pieces; it adds looping and a confirmation boundary, nothing else.

## The confirmation boundary

This is the one thing to understand before using agents: **tools that don't require confirmation are executed automatically inside the loop; tools that do, are not - ever.**

- `requiresConfirmation() === false` (a read-only search, say): the loop executes it itself, appends the `role: tool` result message, and immediately asks the model again.
- `requiresConfirmation() === true` (the default): the moment the model requests it, the loop **stops without executing it** and hands control back to you.

```php
match ($result->status()) {
    AgentStatus::Completed => $result->text(),
    AgentStatus::PendingConfirmation => /* see "Resuming" below */,
    AgentStatus::MaxIterationsReached => /* give up gracefully, or resume with a fresh budget */,
};
```

This isn't a setting you can turn off - it's the same rule enforced everywhere else in this package (`ToolCall::execute()` is always explicit; see [security.md](security.md)). An "agent" that auto-executes confirmation-required tools would defeat the entire point of marking them that way.

## Resuming after confirmation

```php
$toolCall = $result->pendingToolCalls()[0];
$toolResult = $toolCall->execute(); // your app confirmed, now executes explicitly

$resumed = AI::agent()
    ->tools([SearchFlight::class, CreateFlightBooking::class]) // same tools/config as the original run
    ->resume($result->messages(), [['toolCall' => $toolCall, 'result' => $toolResult]]);
```

If a single model turn requested more than one confirmation-required tool, `pendingToolCalls()` holds all of them - `resume()` expects an execution entry for every one before it will call the model again.

`->resume()` starts a fresh `maxIterations` budget - it's a new loop invocation, not a continuation of the same iteration count.

## `AgentResult`

```php
$result->status();              // AgentStatus::Completed | PendingConfirmation | MaxIterationsReached
$result->completed();           // bool
$result->pendingConfirmation();
$result->maxIterationsReached();
$result->text();                // final reply, when completed
$result->messages();            // the running conversation - store this however you like
$result->pendingToolCalls();    // ToolCall[], when pendingConfirmation
$result->executions();          // [{toolCall, result}, ...] - every tool the loop *did* auto-execute, for auditing
$result->iterations();          // how many LLM calls this run/resume made
```

## No forced persistence

`AgentResult::messages()` is a plain OpenAI-style array. LaraDantic doesn't store it, doesn't require a database table, and doesn't assume a particular queue/session strategy for surfacing a pending confirmation to a user across requests - that's entirely your application's call, same as [conversations generally](structured-output.md) in this package.

## Passing context to auto-executed tools

```php
AI::agent()->tools([SearchFlight::class])->run($message, $request->user());
```

Extra arguments after the message are forwarded to every auto-executed tool's `execute()` call, exactly like `ToolCall::execute(...$context)` (see [tool-calling.md](tool-calling.md)).
