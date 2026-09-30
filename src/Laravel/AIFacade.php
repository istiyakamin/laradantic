<?php

declare(strict_types=1);

namespace LaraDantic\Laravel;

use Illuminate\Support\Facades\Facade;
use LaraDantic\AI\AIManager;

/**
 * @method static AIManager provider(string $name)
 * @method static AIManager model(string $model)
 * @method static \LaraDantic\AI\StructuredOutputRequest structured(string $schemaClass)
 * @method static \LaraDantic\AI\ToolCallRequest tools(array<int, class-string<\LaraDantic\Tools\Tool>|\LaraDantic\Tools\Tool> $tools)
 * @method static \LaraDantic\AI\AIResponse chat(array<int, array<string, mixed>> $messages, array<string, mixed> $options = [])
 * @method static void extend(string $name, \LaraDantic\Providers\AIProvider $provider)
 */
class AIFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'laradantic.ai';
    }
}
