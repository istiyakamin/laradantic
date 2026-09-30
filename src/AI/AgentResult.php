<?php

declare(strict_types=1);

namespace LaraDantic\AI;

use LaraDantic\Tools\ToolCall;
use LaraDantic\Tools\ToolResult;

/**
 * The outcome of a single {@see Agent::run()}/{@see Agent::resume()} call.
 */
final class AgentResult
{
    /**
     * @param  array<int, array<string, mixed>>  $messages
     * @param  array<int, ToolCall>  $pendingToolCalls
     * @param  array<int, array{toolCall: ToolCall, result: ToolResult}>  $executions
     */
    public function __construct(
        private readonly AgentStatus $status,
        private readonly array $messages,
        private readonly ?string $text,
        private readonly array $pendingToolCalls,
        private readonly array $executions,
        private readonly int $iterations,
    ) {}

    public function status(): AgentStatus
    {
        return $this->status;
    }

    public function completed(): bool
    {
        return $this->status === AgentStatus::Completed;
    }

    public function pendingConfirmation(): bool
    {
        return $this->status === AgentStatus::PendingConfirmation;
    }

    public function maxIterationsReached(): bool
    {
        return $this->status === AgentStatus::MaxIterationsReached;
    }

    /**
     * The final assistant reply, when {@see self::completed()}.
     */
    public function text(): ?string
    {
        return $this->text;
    }

    /**
     * The running conversation so far - pass this back into {@see Agent::resume()}
     * once pending tool calls have been confirmed and executed.
     *
     * @return array<int, array<string, mixed>>
     */
    public function messages(): array
    {
        return $this->messages;
    }

    /**
     * Tool calls awaiting explicit confirmation, when {@see self::pendingConfirmation()}.
     * The loop stopped without executing these.
     *
     * @return array<int, ToolCall>
     */
    public function pendingToolCalls(): array
    {
        return $this->pendingToolCalls;
    }

    /**
     * Tools that were auto-executed (because they didn't require confirmation)
     * during this call, with their results, in execution order.
     *
     * @return array<int, array{toolCall: ToolCall, result: ToolResult}>
     */
    public function executions(): array
    {
        return $this->executions;
    }

    /**
     * The number of LLM calls made during this run/resume.
     */
    public function iterations(): int
    {
        return $this->iterations;
    }
}
