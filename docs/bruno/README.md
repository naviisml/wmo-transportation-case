# Wmo Utrecht API

Open the collection in Bruno and select the `Local` environment. Set `token` to a personal access token from the web app.

For a booking, set `resident`, `scheduled_at` (a future ISO 8601 timestamp with an offset), and `idempotency_key`. Use the same key and request body when retrying a booking.

The token endpoints require a web session with CSRF protection.
