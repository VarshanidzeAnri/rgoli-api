# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

Get a token from <code>POST /api/v1/auth/login</code> and send it as <code>Authorization: Bearer {TOKEN}</code>. Send <code>Accept-Language: ka|en|ru</code> for translated messages.
