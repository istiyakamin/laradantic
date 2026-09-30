<?php

declare(strict_types=1);

/**
 * Example: structured AI output.
 *
 * Turns free text into a validated, typed object using the same schema
 * from examples/basic-schema. Run this from within a booted Laravel app
 * (the AI facade needs the service container) with a real
 * OPENROUTER_API_KEY configured - or fake the HTTP call as shown below
 * to run it standalone, e.g. in a test.
 */

require __DIR__.'/../basic-schema/UserProfile.php';

use Illuminate\Support\Facades\Http;
use LaraDantic\Examples\BasicSchema\UserProfile;

// In a real request, skip this fake and let AI::structured() call OpenRouter for real.
Http::fake(['*/chat/completions' => Http::response([
    'model' => 'openrouter/some-model',
    'choices' => [[
        'message' => [
            'role' => 'assistant',
            'content' => json_encode(['name' => 'Jane Doe', 'email' => 'jane@example.com']),
        ],
        'finish_reason' => 'stop',
    ]],
])]);

$message = 'My name is Jane Doe, reach me at jane@example.com.';

/** @var UserProfile $profile */
$profile = AI::structured(UserProfile::class)
    ->system('Extract the user\'s profile from the message.')
    ->prompt($message)
    ->run();

echo $profile->name.' <'.$profile->email.'>'.PHP_EOL;
