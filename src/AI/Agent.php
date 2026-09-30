<?php

declare(strict_types=1);

namespace LaraDantic\AI;

use LaraDantic\Providers\AIProvider;
use LaraDantic\Tools\Tool;
use LaraDantic\Tools\ToolCall;
use LaraDantic\Tools\ToolExecutor;
use LaraDantic\Tools\ToolRegistry;
use LaraDantic\Tools\ToolResult;

/**
 * Runs the LLM -> tool call -> tool execution -> tool result -> LLM loop.
 *
 * The loop auto-executes tool calls that don't require confirmation and
 * feeds their results back to the model. The moment it encounters a tool
 * call that does require confirmation, it stops - never executing it - and
 * returns control to the host application via a {@see AgentResult} in the
 * `PendingConfirmation` state. The host confirms, executes it, and calls
 * {@see self::resume()} to continue. This is a hard boundary, not a
 * configurable one: LaraDantic never assumes "the AI said it, therefore it
 * is authorized."
 */
final class Agent
{
    private ?string $systemPrompt = null;

    private int $maxIterations = 5;

    private ToolRegistry $toolRegistry;

    /** @var array<string, mixed> */
    private array $options = [];

    public function __construct(
        private readonly AIProvider $provider,
        private readonly ?string $model = null,
        ?ToolRegistry $toolRegistry = null,
    ) {
        $this->toolRegistry = $toolRegistry ?? new ToolRegistry;
    }

    public function system(string $prompt): self
    {
        $clone = clone $this;
        $clone->systemPrompt = $prompt;

        return $clone;
    }

    public function maxIterations(int $max): self
    {
        $clone = clone $this;
        $clone->maxIterations = $max;

        return $clone;
    }

    /**
     * @param  array<int, class-string<Tool>|Tool>  $tools
     */
    public function tools(array $tools): self
    {
        $registry = new ToolRegistry;

        foreach ($tools as $tool) {
            $registry->register($tool);
        }

        $clone = clone $this;
        $clone->toolRegistry = $registry;

        return $clone;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function options(array $options): self
    {
        $clone = clone $this;
        $clone->options = array_merge($this->options, $options);

        return $clone;
    }

    /**
     * Start a new agent run from a user message.
     */
    public function run(string $message, mixed ...$context): AgentResult
    {
        $messages = [];

        if ($this->systemPrompt !== null) {
            $messages[] = ['role' => 'system', 'content' => $this->systemPrompt];
        }

        $messages[] = ['role' => 'user', 'content' => $message];

        return $this->loop($messages, 0, $context);
    }

    /**
     * Continue a run that stopped for confirmation: supply the tool call(s)
     * from that {@see AgentResult::pendingToolCalls()} together with the
     * {@see ToolResult} obtained by explicitly executing each of them.
     *
     * @param  array<int, array<string, mixed>>  $messages  the paused run's AgentResult::messages()
     * @param  array<int, array{toolCall: ToolCall, result: ToolResult}>  $confirmedExecutions
     */
    public function resume(array $messages, array $confirmedExecutions, mixed ...$context): AgentResult
    {
        foreach ($confirmedExecutions as $execution) {
            $messages[] = $this->toolResultMessage($execution['toolCall'], $execution['result']);
        }

        return $this->loop($messages, 0, $context);
    }

    /**
     * @param  array<int, array<string, mixed>>  $messages
     * @param  array<array-key, mixed>  $context
     */
    private function loop(array $messages, int $iterations, array $context): AgentResult
    {
        $allExecutions = [];

        while (true) {
            if ($iterations >= $this->maxIterations) {
                return new AgentResult(AgentStatus::MaxIterationsReached, $messages, null, [], $allExecutions, $iterations);
            }

            $options = $this->options;

            if ($this->model !== null) {
                $options['model'] ??= $this->model;
            }

            $response = $this->provider->tools($messages, $this->toolRegistry->definitions(), $options);
            $iterations++;

            if (! $response->hasToolCalls()) {
                return new AgentResult(AgentStatus::Completed, $messages, $response->text(), [], $allExecutions, $iterations);
            }

            $messages[] = [
                'role' => 'assistant',
                'content' => $response->text(),
                'tool_calls' => $response->toolCalls(),
            ];

            $toolCalls = (new ToolExecutor($this->toolRegistry))->parse($response);

            $pending = [];

            foreach ($toolCalls as $toolCall) {
                if ($toolCall->requiresConfirmation()) {
                    $pending[] = $toolCall;

                    continue;
                }

                $result = $toolCall->execute(...$context);
                $allExecutions[] = ['toolCall' => $toolCall, 'result' => $result];
                $messages[] = $this->toolResultMessage($toolCall, $result);
            }

            if ($pending !== []) {
                return new AgentResult(AgentStatus::PendingConfirmation, $messages, null, $pending, $allExecutions, $iterations);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function toolResultMessage(ToolCall $toolCall, ToolResult $result): array
    {
        return [
            'role' => 'tool',
            'tool_call_id' => $toolCall->id(),
            'content' => json_encode($result->toArray()),
        ];
    }
}
