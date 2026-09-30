# Example: Basic schema

The minimal case: one class definition, reused for hydration, serialization, JSON Schema, and validation.

```php
use LaraDantic\Examples\BasicSchema\UserProfile;

$profile = UserProfile::from([
    'name' => 'Jane Doe',
    'email' => 'jane@example.com',
]);

$profile->toArray();
// ['name' => 'Jane Doe', 'email' => 'jane@example.com']

UserProfile::jsonSchema();
// ['type' => 'object', 'properties' => [
//     'name' => ['type' => 'string', 'description' => "The user's full name"],
//     'email' => ['type' => 'string', 'description' => "The user's email address", 'format' => 'email'],
// ], 'required' => ['name', 'email']]

UserProfile::validate(['name' => 'Jane Doe', 'email' => 'not-an-email'])->errors();
// ['email' => ['The email field must be a valid email address.']]
```

See [`docs/schemas.md`](../../docs/schemas.md), [`docs/types.md`](../../docs/types.md), and [`docs/validation.md`](../../docs/validation.md) for the full picture.
