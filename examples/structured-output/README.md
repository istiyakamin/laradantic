# Example: Structured AI output

```php
$profile = AI::structured(UserProfile::class)
    ->prompt($message)
    ->run();
```

`$message` is free text; `$profile` comes back as an actual `UserProfile` instance - validated and hydrated, not a raw array you have to trust. See [`example.php`](example.php) for a runnable version (faked via `Http::fake()` so it doesn't need a real API key), and [`docs/structured-output.md`](../../docs/structured-output.md) for the full pipeline this goes through.
