# GBUKPOP x KRJASTIP v1.0.11

- Google OAuth callback now uses the current request host to prevent session/state mismatch between `localhost`, `127.0.0.1`, and ngrok.
- Google OAuth cancellation (`access_denied`) returns safely to login instead of throwing a 500.
- Invalid/expired OAuth state is handled with a user-facing error instead of an exception page.
- Reverse proxy headers are trusted so HTTPS/host detection works through ngrok.
- Mobile hamburger moved to the far right of the navbar and now updates `aria-expanded`.
- Added informational toast styling for non-error OAuth cancellation messages.
