<?php

declare(strict_types=1);

namespace LaraDantic\AI;

enum AgentStatus
{
    /** The model returned a final plain-text reply; no tool calls are pending. */
    case Completed;

    /** The model requested a tool that requires confirmation; the loop stopped and did not execute it. */
    case PendingConfirmation;

    /** The configured maximum number of LLM calls was reached before completion. */
    case MaxIterationsReached;
}
