<?php

declare(strict_types=1);

namespace LaraDantic\Examples\BasicSchema;

use LaraDantic\Schema\Attributes\Description;
use LaraDantic\Schema\Attributes\Format;
use LaraDantic\Schema\Schema;

/**
 * The simplest possible schema: a couple of required strings.
 *
 * Try it:
 *
 *   $profile = UserProfile::from(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
 *   $profile->toArray();
 *   UserProfile::jsonSchema();
 *   UserProfile::validate(['name' => 'Jane Doe', 'email' => 'not-an-email'])->errors();
 */
class UserProfile extends Schema
{
    #[Description('The user\'s full name')]
    public string $name;

    #[Description('The user\'s email address')]
    #[Format('email')]
    public string $email;
}
