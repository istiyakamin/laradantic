# Example: Booking agent

The full flow the package was originally motivated by:

```
User → AI → FlightBooking schema → validation → tool call → confirmation → "CRM"
```

Two tools:

- **`SearchFlight`** - read-only, `requiresConfirmation() === false`. The agent loop executes it automatically and feeds the result back to the model.
- **`CreateFlightBooking`** - has a real side effect, so it keeps `Tool`'s default `requiresConfirmation() === true`. The agent loop stops the moment the model requests it, *without* executing it, and hands control back.

Run [`example.php`](example.php) (from within a booted Laravel app) to see the whole thing end-to-end, faked via `Http::fake()` across three simulated model turns:

1. Model calls `search_flight` → executed automatically → result fed back.
2. Model calls `create_flight_booking` → **loop stops here**, unconfirmed.
3. The script "confirms" (standing in for whatever your app's real confirmation UI looks like), executes the booking explicitly, and resumes the agent → model gives its final reply.

See [`docs/agents.md`](../../docs/agents.md) for the confirmation boundary in depth, and [`docs/security.md`](../../docs/security.md) for why this isn't configurable away.
